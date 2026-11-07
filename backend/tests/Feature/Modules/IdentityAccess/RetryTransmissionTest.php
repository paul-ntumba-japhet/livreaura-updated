<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\TransmissionAttempt;
use Illuminate\Support\Str;

class RetryTransmissionTest extends IdentityAccessTestCase
{
    public function test_supervised_retry_queues_the_same_transmission(): void
    {
        $this->seedServicePolicies();
        $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayloadForRequiredTexts($email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account::query()
            ->where('email_normalise', $email)
            ->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);

        $transmission = MessageTransmission::query()->find($proof['transmission_id']);
        $this->assertNotNull($transmission);
        $transmission->etat = 'failed';
        $transmission->nombre_tentatives = 3;
        $transmission->prochaine_tentative_a = null;
        $transmission->save();

        $actor = $this->activatedAccount();
        $this->grantInternalPermissions($actor, ['reprendre_transmissions']);

        $key = (string) Str::uuid();
        $this->postJson("/api/v1/backoffice/identity-access/transmissions/{$transmission->id}/reprise", [], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'reprise_enregistree')
            ->assertJsonPath('data.transmission.etat', 'queued')
            ->assertJsonPath('data.transmission.nombre_tentatives', 3);

        $this->postJson("/api/v1/backoffice/identity-access/transmissions/{$transmission->id}/reprise", [], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'reprise_enregistree');

        $this->assertSame('queued', $transmission->fresh()?->etat);
        $this->assertSame('reprise_transmission', OutgoingMessage::query()->where('operation', 'transmissions_messages')->value('type'));

        $this->dispatchDueTransmissions();
        $this->assertSame('sent', $transmission->fresh()?->etat);
        $this->assertSame(4, $transmission->fresh()?->nombre_tentatives);
        $this->assertSame(
            'accepted',
            TransmissionAttempt::query()->where('transmission_id', $transmission->id)->orderByDesc('numero')->value('resultat'),
        );
        $this->assertNotNull($this->notifications()->lastSecretFor($account->id, 'email_activation'));
    }

    public function test_sent_transmission_cannot_be_retried_and_list_omits_ciphertext(): void
    {
        $this->seedServicePolicies();
        $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayloadForRequiredTexts($email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account::query()
            ->where('email_normalise', $email)
            ->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);
        $this->dispatchDueTransmissions();

        $actor = $this->activatedAccount();
        $this->grantInternalPermissions($actor, ['reprendre_transmissions']);

        $this->postJson("/api/v1/backoffice/identity-access/transmissions/{$proof['transmission_id']}/reprise", [], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');

        $failed = MessageTransmission::query()->find($proof['transmission_id']);
        $this->assertNotNull($failed);
        $failed->etat = 'retry_wait';
        $failed->save();

        $list = $this->getJson('/api/v1/backoffice/identity-access/transmissions')->assertOk();
        $this->assertSame($failed->id, $list->json('data.transmissions.0.id'));
        $encoded = $list->getContent();
        $this->assertStringNotContainsString($proof['secret'], $encoded);
        $this->assertArrayNotHasKey('contenu_chiffre_temporaire', $list->json('data.transmissions.0'));
    }

    public function test_missing_permission_is_forbidden(): void
    {
        $this->activatedAccount();

        $this->postJson('/api/v1/backoffice/identity-access/transmissions/'.\App\Modules\IdentityAccess\Domain\Support\IdentityUlid::make().'/reprise', [], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');

        $this->getJson('/api/v1/backoffice/identity-access/transmissions')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }
}
