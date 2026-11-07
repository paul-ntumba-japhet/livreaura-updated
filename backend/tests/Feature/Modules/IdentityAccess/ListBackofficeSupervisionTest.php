<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use Illuminate\Support\Str;

class ListBackofficeSupervisionTest extends IdentityAccessTestCase
{
    public function test_lists_transmissions_to_watch_without_secrets(): void
    {
        $this->seedServicePolicies();
        $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayloadForRequiredTexts($email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);

        $transmission = MessageTransmission::query()->find($proof['transmission_id']);
        $this->assertNotNull($transmission);
        $transmission->etat = 'failed';
        $transmission->save();

        $actor = $this->activatedAccount();
        $this->grantInternalPermissions($actor, ['reprendre_transmissions']);

        $response = $this->getJson('/api/v1/backoffice/identity-access/supervision?per_page=50')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'supervision')
            ->assertJsonPath('data.pagination.per_page', 50);

        $this->getJson('/api/v1/backoffice/identity-access/supervision')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 5);

        $payload = (string) json_encode($response->json('data'));
        $this->assertStringNotContainsString($proof['secret'], $payload);
        $this->assertStringNotContainsString('destination_chiffree', $payload);
        $this->assertStringNotContainsString('contenu_chiffre_temporaire', $payload);
        $this->assertStringNotContainsString($email, $payload);

        $row = collect($response->json('data.transmissions'))->firstWhere('id', $transmission->id);
        $this->assertIsArray($row);
        $this->assertSame('failed', $row['etat'] ?? null);
        $this->assertTrue($row['eligible_reprise'] ?? false);
        $this->assertNotEmpty($row['reference'] ?? null);
        $this->assertGreaterThanOrEqual(1, $response->json('data.totaux.echecs'));
        $this->assertNotEmpty($response->json('data.journal_recent'));
        $this->assertTrue($response->json('data.capacites.reprendre'));
    }

    public function test_filters_canal_state_and_journal_tab(): void
    {
        $this->seedServicePolicies();
        $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayloadForRequiredTexts($email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);
        $transmission = MessageTransmission::query()->find($proof['transmission_id']);
        $this->assertNotNull($transmission);
        $transmission->etat = 'sent';
        $transmission->save();

        $actor = $this->activatedAccount();
        $this->grantInternalPermissions($actor, ['reprendre_transmissions']);

        $emailRows = collect($this->getJson('/api/v1/backoffice/identity-access/supervision?canal=email&etat=sent&per_page=50')
            ->assertOk()
            ->json('data.transmissions'))
            ->pluck('id')
            ->all();
        $this->assertContains($transmission->id, $emailRows);

        $failed = collect($this->getJson('/api/v1/backoffice/identity-access/supervision?etat=failed&per_page=50')
            ->assertOk()
            ->json('data.transmissions'))
            ->pluck('id')
            ->all();
        $this->assertNotContains($transmission->id, $failed);

        $sms = collect($this->getJson('/api/v1/backoffice/identity-access/supervision?canal=sms&per_page=50')
            ->assertOk()
            ->json('data.transmissions'))
            ->pluck('id')
            ->all();
        $this->assertNotContains($transmission->id, $sms);

        $journal = $this->getJson('/api/v1/backoffice/identity-access/supervision?onglet=journal')
            ->assertOk();
        $this->assertSame('journal', $journal->json('data.onglet'));
        $this->assertNotEmpty($journal->json('data.evenements'));
        $this->assertGreaterThanOrEqual(1, $journal->json('data.totaux.attente_accuse'));

        $this->getJson('/api/v1/backoffice/identity-access/supervision?onglet=reprises')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'reprises');
    }

    public function test_refuses_the_directory_without_permission(): void
    {
        $this->activatedAccount();

        $this->getJson('/api/v1/backoffice/identity-access/supervision')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }
}
