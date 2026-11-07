<?php

namespace Tests\Feature\Integration\Messaging;

use App\Modules\IdentityAccess\Application\Jobs\SendIdentityNotificationJob;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\TransmissionAttempt;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\Feature\Modules\IdentityAccess\IdentityAccessTestCase;

class IdentityOutboxTest extends IdentityAccessTestCase
{
    public function test_identity_outbox_does_not_send_until_dispatch(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($text, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $this->assertNull($this->notifications()->lastSecretFor($account->id));

        $transmission = MessageTransmission::query()
            ->where('compte_destinataire_id', $account->id)
            ->where('etat', 'queued')
            ->first();
        $this->assertNotNull($transmission);

        $this->dispatchDueTransmissions();

        $this->assertNotNull($this->notifications()->lastSecretFor($account->id, 'email_activation'));
        $this->assertSame('sent', $transmission->fresh()?->etat);
        $this->assertSame(
            'accepted',
            TransmissionAttempt::query()->where('transmission_id', $transmission->id)->value('resultat'),
        );
    }

    public function test_identity_job_payload_contains_only_the_transmission_id(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($text, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);

        Bus::fake();
        $this->artisan('a1:dispatch-transmissions')->assertSuccessful();

        Bus::assertDispatched(SendIdentityNotificationJob::class, function (SendIdentityNotificationJob $job) use ($proof): bool {
            $serialized = serialize($job);

            return $job->transmissionId === $proof['transmission_id']
                && ! str_contains($serialized, $proof['secret']);
        });
    }
}
