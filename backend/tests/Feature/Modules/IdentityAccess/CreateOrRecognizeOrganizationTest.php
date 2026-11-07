<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationType;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use Illuminate\Support\Str;

class CreateOrRecognizeOrganizationTest extends IdentityAccessTestCase
{
    public function test_guest_cannot_create_an_organization(): void
    {
        $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => IdentityUlid::make(),
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertUnauthorized();
    }

    public function test_pending_account_is_ineligible(): void
    {
        $this->seedServicePolicies();
        $type = $this->seedOrganizationType();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($text, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $this->loginAs($email);

        $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'portee_insuffisante');
    }

    public function test_new_organization_installs_the_declaring_principal(): void
    {
        $account = $this->activatedAccount();
        $type = $this->seedOrganizationType();
        $key = (string) Str::uuid();

        $first = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
            'ville' => 'Kinshasa',
        ], [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'creee')
            ->assertJsonPath('data.prochaine_action', 'compte')
            ->assertJsonPath('data.organisation.raison_sociale', 'Maison du Fleuve')
            ->assertJsonPath('data.adhesion.principal', true)
            ->assertJsonPath('data.adhesion.statut', 'active')
            ->assertJsonPath('data.adhesion.permissions.0', 'deposer_kyc');

        $organizationId = $first->json('data.organisation.id');
        $this->assertTrue(IdentityUlid::isValid($organizationId));

        $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
            'ville' => 'Kinshasa',
        ], [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.organisation.id', $organizationId);

        $this->assertSame(1, Organization::query()->where('id', $organizationId)->count());
        $this->assertSame(1, OrganizationMembership::query()->where('organisation_id', $organizationId)->where('principal', 1)->where('statut', 'active')->count());
        $this->assertSame(1, AccountEvent::query()->where('compte_sujet_id', $account->id)->where('type', 'adhesion_organisation_modifiee')->count());

        $me = $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.organisations.0.id', $organizationId)
            ->assertJsonPath('data.organisations.0.adhesion.principal', true);

        $this->assertArrayNotHasKey('representant_verifie', $me->json('data.organisations.0'));
    }

    public function test_name_match_does_not_grant_access_until_confirmed_or_claimed(): void
    {
        $account = $this->activatedAccount();
        $type = $this->seedOrganizationType();
        $existing = $this->insertOrganization($type, 'Maison du Fleuve', 'CD');

        $lookup = $this->getJson('/api/v1/identity-access/organisations/correspondances?'.http_build_query([
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'maison du fleuve',
            'pays' => 'CD',
        ]))->assertOk();

        $this->assertSame($existing->id, $lookup->json('data.correspondances.0.id'));
        $this->assertSame(0, OrganizationMembership::query()->where('compte_id', $account->id)->count());

        $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('data.statut', 'correspondances_possibles')
            ->assertJsonPath('data.prochaine_action', 'confirmer_ou_revendiquer')
            ->assertJsonPath('data.correspondances.0.id', $existing->id);

        $this->assertSame(0, OrganizationMembership::query()->where('compte_id', $account->id)->count());

        $created = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
            'confirmer_nouvelle' => true,
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'creee');

        $this->assertNotSame($existing->id, $created->json('data.organisation.id'));
        $this->assertSame(2, Organization::query()->where('type_organisation_id', $type->id)->count());
    }

    public function test_claiming_an_existing_organization_opens_a_representation_request(): void
    {
        $account = $this->activatedAccount();
        $type = $this->seedOrganizationType();
        $existing = $this->insertOrganization($type, 'Editions Kivu', 'CD');
        $key = (string) Str::uuid();

        $first = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Editions Kivu',
            'pays' => 'CD',
            'organisation_id' => $existing->id,
        ], [
            'Idempotency-Key' => $key,
        ])->assertStatus(202)
            ->assertJsonPath('data.statut', 'examen')
            ->assertJsonPath('data.adhesion', null);

        $reference = $first->json('data.demande.reference');
        $this->assertNotEmpty($reference);

        $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Editions Kivu',
            'pays' => 'CD',
            'organisation_id' => $existing->id,
        ], [
            'Idempotency-Key' => $key,
        ])->assertStatus(202)
            ->assertJsonPath('data.demande.reference', $reference);

        $this->assertSame(0, OrganizationMembership::query()->where('compte_id', $account->id)->count());
        $this->assertSame(1, SupportRequest::query()->where('demandeur_compte_id', $account->id)->where('type', 'representation')->where('organisation_id', $existing->id)->count());
        $existing->refresh();
        $this->assertNull(OrganizationMembership::query()->where('organisation_id', $existing->id)->where('compte_id', $account->id)->first());
    }

    public function test_unknown_organization_is_rejected(): void
    {
        $this->activatedAccount();
        $type = $this->seedOrganizationType();

        $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Inconnue',
            'pays' => 'CD',
            'organisation_id' => IdentityUlid::make(),
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'organisation_inconnue');
    }

    public function test_unknown_type_is_rejected(): void
    {
        $this->activatedAccount();

        $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => IdentityUlid::make(),
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'type_inconnu');
    }

    private function insertOrganization(OrganizationType $type, string $name, string $country): Organization
    {
        $organization = new Organization;
        $organization->id = IdentityUlid::make();
        $organization->type_organisation_id = $type->id;
        $organization->raison_sociale = $name;
        $organization->pays = $country;
        $organization->statut_operationnel = 'active';
        $organization->cree_a = now();
        $organization->save();

        return $organization;
    }
}
