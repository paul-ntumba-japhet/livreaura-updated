<?php

namespace App\Modules\IdentityAccess\Application\Actions\Sessions;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;

class RecordSessionAccessChange
{
    public function __construct(private IdentityOutboxPublisher $outbox) {}

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $payload
     */
    public function __invoke(
        string $accountId,
        string $operation,
        string $motif,
        string $correlationId,
        string $idempotencyKey,
        string $intentionFingerprint,
        array $state,
        array $payload,
    ): void {
        $event = new AccountEvent;
        $event->id = IdentityUlid::make();
        $event->compte_sujet_id = $accountId;
        $event->type = 'session_acces_modifie';
        $event->date = now();
        $event->nouvel_etat = json_encode($state, JSON_THROW_ON_ERROR);
        $event->motif = $motif;
        $event->origine_systeme = $operation;
        $event->correlation = $correlationId;
        $event->save();

        $this->outbox->record(
            idempotencyKey: $idempotencyKey,
            type: 'session_acces_modifie',
            operation: $operation,
            correlation: $correlationId,
            intentionFingerprint: $intentionFingerprint,
            payload: $payload,
            accountId: $accountId,
            accountEventId: $event->id,
        );
    }
}
