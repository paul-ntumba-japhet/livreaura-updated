<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Application\Actions\Messaging\ReceiveAccountReviewInstruction;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageReception;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;

class ReceiveAccountReviewInstructionTest extends IdentityAccessTestCase
{
    public function test_instruction_creates_exam_demand_without_suspending_on_chargeback(): void
    {
        $account = $this->activatedAccount();
        $action = $this->app->make(ReceiveAccountReviewInstruction::class);
        $eventId = 'a6-22-'.strtolower(IdentityUlid::make());
        $payload = $this->instruction($account->id, $eventId, 'chargeback', 'Chargeback declare par le reseau.');

        $first = $action($payload, IdentityUlid::make());
        $second = $action($payload, IdentityUlid::make());

        $this->assertSame('engage_local', $first['niveau_acquittement']);
        $this->assertSame($first['reference_resultat'], $second['reference_resultat']);
        $this->assertSame($first['demande_id'], $second['demande_id']);
        $this->assertSame(AccountStatus::Active, $account->fresh()?->statut);
        $this->assertSame(1, SupportRequest::query()->where('type', 'examen_compte_conformite')->where('compte_concerne_id', $account->id)->count());
        $this->assertSame(1, ServiceTask::query()->where('demande_support_id', $first['demande_id'])->count());
        $this->assertSame(1, MessageReception::query()->where('evenement_externe_id', $eventId)->where('consommateur', 'A1-27')->count());
        $this->assertSame('engage_local', MessageReception::query()->where('evenement_externe_id', $eventId)->value('niveau_acquittement'));
        $this->assertSame('examen_compte_recu', OutgoingMessage::query()->where('operation', 'A1-27')->value('type'));
        $this->assertSame('A1-27', AccountEvent::query()->where('compte_sujet_id', $account->id)->where('type', 'examen_compte_recu')->value('origine_systeme'));
        $state = json_decode((string) AccountEvent::query()->where('compte_sujet_id', $account->id)->where('type', 'examen_compte_recu')->value('nouvel_etat'), true);
        $this->assertFalse($state['suspension_automatique'] ?? true);
    }

    public function test_same_event_with_different_intention_conflicts(): void
    {
        $account = $this->activatedAccount();
        $action = $this->app->make(ReceiveAccountReviewInstruction::class);
        $eventId = 'a6-22-'.strtolower(IdentityUlid::make());

        $instructionId = IdentityUlid::make();
        $action($this->instruction($account->id, $eventId, 'chargeback', 'Premiere intention.', $instructionId), IdentityUlid::make());

        try {
            $action($this->instruction($account->id, $eventId, 'chargeback', 'Intention differente.', $instructionId), IdentityUlid::make());
            $this->fail('Expected a conflicting instruction to be rejected.');
        } catch (IdentityAccessException $exception) {
            $this->assertSame('intention_conflictuelle', $exception->errorCode);
            $this->assertSame(409, $exception->status);
        }

        $this->assertSame(1, SupportRequest::query()->where('type', 'examen_compte_conformite')->count());
        $this->assertSame(AccountStatus::Active, $account->fresh()?->statut);
    }

    public function test_unknown_account_is_rejected_without_inbox_effect(): void
    {
        $this->seedServicePolicies();
        $action = $this->app->make(ReceiveAccountReviewInstruction::class);

        try {
            $action($this->instruction(IdentityUlid::make(), 'a6-22-inconnu', 'fraude', 'Compte introuvable.'), IdentityUlid::make());
            $this->fail('Expected an unknown account to be rejected.');
        } catch (IdentityAccessException $exception) {
            $this->assertSame('compte_inconnu', $exception->errorCode);
            $this->assertSame(404, $exception->status);
        }

        $this->assertSame(0, MessageReception::query()->count());
        $this->assertSame(0, SupportRequest::query()->where('type', 'examen_compte_conformite')->count());
    }

    /**
     * @return array{evenement_externe_id: string, producteur: string, correlation: string, schema_message_version: int, compte_id: string, instruction_id: string, type: string, cause: ?string, motif: string}
     */
    private function instruction(string $accountId, string $eventId, string $cause, string $motif, ?string $instructionId = null): array
    {
        return [
            'evenement_externe_id' => $eventId,
            'producteur' => 'A6',
            'correlation' => IdentityUlid::make(),
            'schema_message_version' => 1,
            'compte_id' => $accountId,
            'instruction_id' => $instructionId ?? IdentityUlid::make(),
            'type' => 'demander_examen_compte',
            'cause' => $cause,
            'motif' => $motif,
        ];
    }
}
