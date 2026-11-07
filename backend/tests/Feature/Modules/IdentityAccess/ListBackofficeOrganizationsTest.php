<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use Illuminate\Support\Str;

class ListBackofficeOrganizationsTest extends IdentityAccessTestCase
{
    public function test_lists_organizations_with_declaring_principal(): void
    {
        $owner = $this->activatedAccount();
        $type = $this->seedOrganizationType();
        $created = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
            'ville' => 'Kinshasa',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $organizationId = $created->json('data.organisation.id');
        $this->assertNotEmpty($organizationId);

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['transferer_principal', 'verifier_representation']);

        $response = $this->getJson('/api/v1/backoffice/identity-access/organisations?per_page=50')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'organisations')
            ->assertJsonPath('data.pagination.per_page', 50);

        $this->getJson('/api/v1/backoffice/identity-access/organisations')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 5);

        $payload = (string) json_encode($response->json('data'));
        $this->assertStringNotContainsString('hash_mdp', $payload);
        $this->assertStringNotContainsString($owner->email_normalise, $payload);

        $row = collect($response->json('data.organisations'))->firstWhere('id', $organizationId);
        $this->assertIsArray($row);
        $this->assertSame('Maison du Fleuve', $row['raison_sociale']);
        $this->assertSame('Kinshasa', $row['ville']);
        $this->assertSame($owner->id, $row['representant']['id'] ?? null);
        $this->assertSame('declaree', $row['representation']);
        $this->assertSame('non_verifiee', $row['verification']);
        $this->assertGreaterThanOrEqual(1, $response->json('data.totaux.organisations'));
    }

    public function test_filters_search_and_lists_open_representation_requests(): void
    {
        $owner = $this->activatedAccount();
        $type = $this->seedOrganizationType();
        $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
            'ville' => 'Kinshasa',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $existing = Organization::query()->where('raison_sociale', 'Maison du Fleuve')->first();
        $this->assertNotNull($existing);

        $claimant = $this->activatedAccount();
        $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
            'organisation_id' => $existing->id,
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(202);

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['traiter_support']);

        $names = collect($this->getJson('/api/v1/backoffice/identity-access/organisations?q='.urlencode('Maison du Fleuve'))
            ->assertOk()
            ->json('data.organisations'))
            ->pluck('raison_sociale')
            ->all();
        $this->assertContains('Maison du Fleuve', $names);

        $claims = $this->getJson('/api/v1/backoffice/identity-access/organisations?onglet=representation')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'representation');
        $this->assertGreaterThanOrEqual(1, $claims->json('data.totaux.rattachements'));
        $this->assertContains(
            $existing->id,
            collect($claims->json('data.demandes'))->pluck('organisation.id')->all(),
        );
        $this->assertSame($claimant->id, collect($claims->json('data.demandes'))->firstWhere('organisation.id', $existing->id)['demandeur']['id'] ?? null);

        $this->getJson('/api/v1/backoffice/identity-access/organisations?onglet=transfert')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'transfert');
    }

    public function test_refuses_the_directory_without_permission(): void
    {
        $this->activatedAccount();

        $this->getJson('/api/v1/backoffice/identity-access/organisations')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }
}
