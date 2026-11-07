<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Application\Actions\Messaging\ReceiveAccountReviewInstruction;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageReception;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class IdentitySchedulerCommandsTest extends IdentityAccessTestCase
{
    public function test_resume_inbox_completes_an_untreated_a6_reception(): void
    {
        $account = $this->activatedAccount();
        $eventId = 'a6-22-'.strtolower(IdentityUlid::make());
        $instructionId = IdentityUlid::make();
        $motif = 'Chargeback declare, examen a reprendre.';
        $fingerprint = SecretHasher::hmac(json_encode([
            'compte_id' => $account->id,
            'instruction_id' => $instructionId,
            'type' => 'demander_examen_compte',
            'cause' => 'chargeback',
            'motif' => $motif,
        ], JSON_THROW_ON_ERROR));

        $reception = new MessageReception;
        $reception->id = IdentityUlid::make();
        $reception->evenement_externe_id = $eventId;
        $reception->producteur = ReceiveAccountReviewInstruction::PRODUCER;
        $reception->consommateur = ReceiveAccountReviewInstruction::CONSUMER;
        $reception->correlation = IdentityUlid::make();
        $reception->recu_a = now()->subMinute();
        $reception->empreinte_intention = $fingerprint;
        $reception->schema_message_version = 1;
        $reception->donnees_minimales = [
            'compte_id' => $account->id,
            'instruction_id' => $instructionId,
            'type' => 'demander_examen_compte',
            'cause' => 'chargeback',
            'motif' => $motif,
        ];
        $reception->niveau_acquittement = 'recu_durable';
        $reception->save();

        $this->artisan('a1:resume-inbox')->assertSuccessful();

        $fresh = $reception->fresh();
        $this->assertNotNull($fresh?->traite_a);
        $this->assertSame('engage_local', $fresh?->niveau_acquittement);
        $this->assertSame(1, SupportRequest::query()->where('type', 'examen_compte_conformite')->where('compte_concerne_id', $account->id)->count());
        $this->assertNotNull($fresh?->reference_resultat);
    }

    public function test_expire_challenges_cancels_pending_transmissions_and_purges_secrets(): void
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

        Carbon::setTestNow(now()->addHours(25));

        try {
            $this->artisan('a1:expire-challenges')->assertSuccessful();

            $transmission = MessageTransmission::query()->find($proof['transmission_id']);
            $this->assertSame('cancelled', $transmission?->etat);
            $this->assertNull($transmission?->contenu_chiffre_temporaire);
            $this->assertNotNull($transmission?->contenu_purge_a);
            $this->assertNull(\App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge::query()->where('id', $proof['challenge_id'])->value('revoque_a'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_purge_transient_secrets_clears_sent_ciphertext(): void
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

        $this->assertNotNull(MessageTransmission::query()->find($proof['transmission_id'])?->contenu_chiffre_temporaire);

        $this->artisan('a1:purge-transient-secrets')->assertSuccessful();

        $transmission = MessageTransmission::query()->find($proof['transmission_id']);
        $this->assertSame('sent', $transmission?->etat);
        $this->assertNull($transmission?->contenu_chiffre_temporaire);
        $this->assertNotNull($transmission?->contenu_purge_a);
    }

    public function test_monitor_escalates_an_overdue_task_once_without_rejecting(): void
    {
        $account = $this->activatedAccount();
        $action = $this->app->make(ReceiveAccountReviewInstruction::class);
        $result = $action([
            'evenement_externe_id' => 'a6-22-'.strtolower(IdentityUlid::make()),
            'producteur' => 'A6',
            'correlation' => IdentityUlid::make(),
            'schema_message_version' => 1,
            'compte_id' => $account->id,
            'instruction_id' => IdentityUlid::make(),
            'type' => 'demander_examen_compte',
            'cause' => 'fraude',
            'motif' => 'Examen pour controle de retard.',
        ], IdentityUlid::make());

        $taskId = $result['tache_id'];
        ServiceTask::query()->where('id', $taskId)->update([
            'echeance_prise_a' => now()->subHour(),
            'echeance_reponse_a' => now()->subMinutes(10),
        ]);

        $this->artisan('a1:monitor-service-tasks')->assertSuccessful();
        $this->artisan('a1:monitor-service-tasks')->assertSuccessful();

        $this->assertSame(1, ServiceTaskEvent::query()->where('tache_id', $taskId)->where('type', 'escalade')->count());
        $task = ServiceTask::query()->find($taskId);
        $this->assertSame('a_affecter', $task?->etat_travail);
        $this->assertSame('urgente', $task?->priorite);
        $this->assertNull($task?->responsable_id);
        $this->assertNull($task?->clos_a);
    }
}
