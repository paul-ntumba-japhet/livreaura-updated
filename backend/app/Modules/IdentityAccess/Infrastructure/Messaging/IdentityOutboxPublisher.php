<?php

namespace App\Modules\IdentityAccess\Infrastructure\Messaging;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;

class IdentityOutboxPublisher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(
        string $idempotencyKey,
        string $type,
        string $operation,
        string $correlation,
        string $intentionFingerprint,
        array $payload,
        ?string $accountId = null,
        ?string $accountEventId = null,
        ?string $challengeId = null,
        ?string $demandeId = null,
        ?string $dossierId = null,
        ?string $kycEventId = null,
    ): OutgoingMessage {
        $existing = OutgoingMessage::query()->where('cle_idempotence', $idempotencyKey)->first();

        if ($existing !== null) {
            return $existing;
        }

        $message = new OutgoingMessage;
        $message->id = IdentityUlid::make();
        $message->cle_idempotence = $idempotencyKey;
        $message->type = $type;
        $message->operation = $operation;
        $message->correlation = $correlation;
        $message->compte_id = $accountId;
        $message->defi_id = $challengeId;
        $message->demande_id = $demandeId;
        $message->dossier_id = $dossierId;
        $message->evenement_compte_id = $accountEventId;
        $message->evenement_kyc_id = $kycEventId;
        $message->producteur = 'A1';
        $message->schema_message_version = 1;
        $message->empreinte_intention = $intentionFingerprint;
        $message->donnees_minimales = $payload;
        $message->cree_a = now();
        $message->save();

        return $message;
    }
}
