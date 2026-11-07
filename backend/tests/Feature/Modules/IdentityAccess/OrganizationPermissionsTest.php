<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationPermission;
use Illuminate\Support\Str;

class OrganizationPermissionsTest extends IdentityAccessTestCase
{
    public function test_declaring_principal_receives_only_deposit_permission_without_a_verified_badge(): void
    {
        $account = $this->activatedAccount();
        $type = $this->seedOrganizationType();

        $created = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
            'ville' => 'Kinshasa',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated()
            ->assertJsonPath('data.adhesion.permissions', ['deposer_kyc']);

        $organizationId = $created->json('data.organisation.id');
        $membership = OrganizationMembership::query()
            ->where('organisation_id', $organizationId)
            ->where('compte_id', $account->id)
            ->where('principal', 1)
            ->first();

        $this->assertNotNull($membership);

        $granted = $membership->permissions()->pluck('code')->all();
        $this->assertSame(['deposer_kyc'], $granted);

        $catalog = OrganizationPermission::query()->pluck('code')->all();
        $this->assertContains('deposer_kyc', $catalog);
        $this->assertContains('deposer_publication', $catalog);
        $this->assertContains('soumettre_publication', $catalog);
        $this->assertContains('consulter_publication', $catalog);
        $this->assertContains('retirer_publication', $catalog);

        foreach (['deposer_publication', 'soumettre_publication', 'consulter_publication', 'retirer_publication', 'repondre_conformite'] as $code) {
            $this->assertNotContains($code, $granted);
        }

        $me = $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.organisations.0.id', $organizationId)
            ->assertJsonPath('data.organisations.0.adhesion.principal', true)
            ->assertJsonPath('data.organisations.0.adhesion.permissions', ['deposer_kyc']);

        $this->assertArrayNotHasKey('representant_verifie', $me->json('data.organisations.0'));
        $this->assertArrayNotHasKey('verifie', $me->json('data.organisations.0'));

        $event = AccountEvent::query()
            ->where('compte_sujet_id', $account->id)
            ->where('type', 'adhesion_organisation_modifiee')
            ->first();
        $this->assertNotNull($event);
        $payload = json_decode((string) $event->nouvel_etat, true);
        $this->assertIsArray($payload);
        $this->assertSame('deposer_kyc', $payload['permission'] ?? null);
        $this->assertFalse($payload['representant_verifie'] ?? true);
    }
}
