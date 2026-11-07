<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Application\Jobs\SendIdentityNotificationJob;
use App\Modules\IdentityAccess\Domain\Contracts\IdentityNotificationSender;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\TransmissionAttempt;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use RuntimeException;

class IdentityOutboxTest extends IdentityAccessTestCase
{
    public function test_registration_does_not_send_until_the_outbox_is_dispatched(): void
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
        $this->assertSame('sent', $transmission->fresh()->etat);
        $this->assertSame(
            'accepted',
            TransmissionAttempt::query()->where('transmission_id', $transmission->id)->value('resultat'),
        );
    }

    public function test_job_payload_contains_only_the_transmission_id(): void
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

    public function test_resend_cancels_the_previous_queued_transmission(): void
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
        $first = $this->issuedChallengeProof($account->id);

        Carbon::setTestNow(now()->addMinutes(2));

        $this->postJson('/api/v1/identity-access/defis/renvoi', [
            'email' => $email,
        ])->assertStatus(202);

        $this->assertSame('cancelled', MessageTransmission::query()->find($first['transmission_id'])?->etat);
        $this->assertNotNull(VerificationChallenge::query()->find($first['challenge_id'])?->revoque_a);

        $second = $this->issuedChallengeProof($account->id);
        $this->assertNotSame($first['challenge_id'], $second['challenge_id']);
        $this->assertSame(
            'queued',
            MessageTransmission::query()->find($second['transmission_id'])?->etat,
        );
    }

    public function test_revoked_challenge_is_cancelled_without_sending(): void
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

        VerificationChallenge::query()
            ->where('id', $proof['challenge_id'])
            ->update(['revoque_a' => now()]);

        $this->dispatchDueTransmissions();

        $this->assertNull($this->notifications()->lastSecretFor($account->id));
        $this->assertSame('cancelled', MessageTransmission::query()->find($proof['transmission_id'])?->etat);
        $this->assertSame(
            'cancelled',
            TransmissionAttempt::query()->where('transmission_id', $proof['transmission_id'])->value('resultat'),
        );
    }

    public function test_stale_worker_does_not_overwrite_a_newer_owner(): void
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

        $this->app->instance(IdentityNotificationSender::class, new class($this->notifications()) implements IdentityNotificationSender
        {
            public function __construct(private IdentityNotificationSender $inner) {}

            public function sendEmailActivation(Account $account, string $challengeId, string $secret): void
            {
                MessageTransmission::query()
                    ->where('compte_destinataire_id', $account->id)
                    ->update(['pris_par' => 'other-worker']);

                $this->inner->sendEmailActivation($account, $challengeId, $secret);
            }

            public function sendPasswordReset(Account $account, string $challengeId, string $secret): void
            {
                $this->inner->sendPasswordReset($account, $challengeId, $secret);
            }

            public function sendPasswordChanged(Account $account): void
            {
                $this->inner->sendPasswordChanged($account);
            }
        });

        $this->dispatchDueTransmissions();

        $transmission = MessageTransmission::query()->find($proof['transmission_id']);
        $this->assertNotNull($transmission);
        $this->assertSame('processing', $transmission->etat);
        $this->assertNull($transmission->envoye_a);
        $this->assertSame('other-worker', $transmission->pris_par);
        $this->assertSame(
            'started',
            TransmissionAttempt::query()->where('transmission_id', $transmission->id)->value('resultat'),
        );
    }

    public function test_timeout_keeps_the_transmission_in_retry_wait(): void
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

        $this->app->instance(IdentityNotificationSender::class, new class implements IdentityNotificationSender
        {
            public function sendEmailActivation(Account $account, string $challengeId, string $secret): void
            {
                throw new RuntimeException('smtp timeout');
            }

            public function sendPasswordReset(Account $account, string $challengeId, string $secret): void {}

            public function sendPasswordChanged(Account $account): void {}
        });

        $this->dispatchDueTransmissions();

        $transmission = MessageTransmission::query()->find($proof['transmission_id']);
        $this->assertNotNull($transmission);
        $this->assertSame('retry_wait', $transmission->etat);
        $this->assertNotNull($transmission->prochaine_tentative_a);
        $this->assertTrue($transmission->prochaine_tentative_a->gt(now()));
        $this->assertSame(
            'unknown',
            TransmissionAttempt::query()->where('transmission_id', $transmission->id)->value('resultat'),
        );

        $this->dispatchDueTransmissions();
        $this->assertSame('retry_wait', $transmission->fresh()->etat);
        $this->assertSame(1, TransmissionAttempt::query()->where('transmission_id', $transmission->id)->count());
    }

    public function test_password_reset_is_sent_only_after_dispatch(): void
    {
        $this->seedServicePolicies();
        $account = Account::factory()->active()->create();

        $this->postJson('/api/v1/identity-access/recuperations', [
            'email' => $account->email_normalise,
        ])->assertStatus(202);

        $this->assertNull($this->notifications()->lastSecretFor($account->id, 'password_reset'));

        $this->dispatchDueTransmissions();

        $this->assertNotNull($this->notifications()->lastSecretFor($account->id, 'password_reset'));
        $this->assertSame(
            'sent',
            MessageTransmission::query()->where('compte_destinataire_id', $account->id)->value('etat'),
        );
    }

    public function test_minimal_payload_does_not_contain_the_challenge_secret(): void
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

        $payload = json_encode(
            OutgoingMessage::query()->where('defi_id', $proof['challenge_id'])->value('donnees_minimales'),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString($proof['secret'], $payload);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
