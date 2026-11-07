<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\PersonalProfile;

class ListBackofficeAccountsTest extends IdentityAccessTestCase
{
    public function test_lists_known_accounts_without_exposing_secrets(): void
    {
        $reader = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte']);

        $response = $this->getJson('/api/v1/backoffice/identity-access/comptes?per_page=50')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 50);

        $this->getJson('/api/v1/backoffice/identity-access/comptes')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 6);

        $payload = json_encode($response->json('data'));
        $this->assertIsString($payload);
        $this->assertStringNotContainsString('hash_mdp', $payload);
        $this->assertStringNotContainsString($reader->email_normalise, $payload);
        $this->assertStringNotContainsString($reviewer->email_normalise, $payload);

        $ids = collect($response->json('data.comptes'))->pluck('id')->all();
        $this->assertContains($reader->id, $ids);
        $this->assertContains($reviewer->id, $ids);

        $reviewerRow = collect($response->json('data.comptes'))->firstWhere('id', $reviewer->id);
        $this->assertIsArray($reviewerRow);
        $this->assertSame(DestinationMasker::email($reviewer->email_normalise), $reviewerRow['email_masque']);
        $this->assertTrue($reviewerRow['email_verifie']);
        $this->assertSame('Amina', $reviewerRow['prenom']);
        $this->assertSame('Kabila', $reviewerRow['nom']);
        $this->assertContains('reader', $reviewerRow['roles']);
        $this->assertNotNull($reviewerRow['derniere_connexion_a']);
        $this->assertGreaterThanOrEqual(2, $response->json('data.totaux.total'));
        $this->assertGreaterThanOrEqual(2, $response->json('data.totaux.active'));
    }

    public function test_filters_by_status_search_and_email_verification(): void
    {
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['traiter_support']);

        $pending = Account::factory()->create([
            'email_normalise' => 'chantal.'.strtolower($reviewer->id).'@example.test',
        ]);
        PersonalProfile::factory()->create([
            'compte_id' => $pending->id,
            'prenom' => 'Chantal',
            'nom' => 'Mwamba',
        ]);

        $pendingList = $this->getJson('/api/v1/backoffice/identity-access/comptes?statut=pending&per_page=50')
            ->assertOk();
        $this->assertContains($pending->id, collect($pendingList->json('data.comptes'))->pluck('id')->all());
        $pendingRow = collect($pendingList->json('data.comptes'))->firstWhere('id', $pending->id);
        $this->assertIsArray($pendingRow);
        $this->assertFalse($pendingRow['email_verifie']);

        $this->getJson('/api/v1/backoffice/identity-access/comptes?q=Chantal')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.comptes.0.id', $pending->id);

        $search = $this->getJson('/api/v1/backoffice/identity-access/comptes?q='.urlencode($pending->email_normalise))
            ->assertOk()
            ->assertJsonPath('data.comptes.0.id', $pending->id);

        $this->assertStringNotContainsString(
            $pending->email_normalise,
            (string) json_encode($search->json('data')),
        );

        $unverified = $this->getJson('/api/v1/backoffice/identity-access/comptes?email_verifie=non&per_page=50')
            ->assertOk();
        $this->assertContains($pending->id, collect($unverified->json('data.comptes'))->pluck('id')->all());

        $amina = collect($this->getJson('/api/v1/backoffice/identity-access/comptes?role=reader&q=Amina')
            ->assertOk()
            ->json('data.comptes'))
            ->pluck('prenom')
            ->all();
        $this->assertContains('Amina', $amina);
        $this->assertNotContains('Chantal', $amina);
    }

    public function test_refuses_a_directory_without_account_permission(): void
    {
        $this->activatedAccount();

        $this->getJson('/api/v1/backoffice/identity-access/comptes')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }
}
