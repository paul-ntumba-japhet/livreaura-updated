<?php

namespace App\Modules\IdentityAccess\Application\Actions\Messaging;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;

class RetryTransmission
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $actor,
        string $transmissionId,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $actor,
            $transmissionId,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
            if (! ($this->hasPermission)($actor, 'reprendre_transmissions')) {
                throw new IdentityAccessException(
                    'permission_insuffisante',
                    'Cette operation interne n est pas autorisee.',
                    403,
                );
            }

            $existingMessage = OutgoingMessage::query()->where('cle_idempotence', $idempotencyKey)->first();

            if ($existingMessage !== null) {
                if ($existingMessage->empreinte_intention !== $intentionFingerprint) {
                    throw new IdentityAccessException(
                        'intention_conflictuelle',
                        'Cette intention entre en conflit avec une demande deja enregistree.',
                        409,
                    );
                }

                $payload = is_array($existingMessage->donnees_minimales) ? $existingMessage->donnees_minimales : [];
                unset($payload['http_status']);

                return $payload;
            }

            $transmission = MessageTransmission::query()
                ->with('message')
                ->where('id', $transmissionId)
                ->lockForUpdate()
                ->first();

            if ($transmission === null) {
                throw new IdentityAccessException('transmission_inconnue', 'Cette transmission n est pas visible.', 404);
            }

            if (! in_array($transmission->etat, ['failed', 'retry_wait'], true)) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette transmission n est pas eligible a une reprise.',
                    409,
                );
            }

            $now = now();
            $transmission->etat = 'queued';
            $transmission->prochaine_tentative_a = $now;
            $transmission->pris_par = null;
            $transmission->bail_jusqua = null;
            $transmission->save();

            $payload = [
                'http_status' => 200,
                'statut' => 'reprise_enregistree',
                'prochaine_action' => 'dispatch',
                'transmission' => $this->serialize($transmission),
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'reprise_transmission',
                operation: 'transmissions_messages',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $transmission->compte_destinataire_id,
            );

            unset($payload['http_status']);

            return $payload;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(MessageTransmission $transmission): array
    {
        $message = $transmission->message;

        return [
            'id' => $transmission->id,
            'etat' => $transmission->etat,
            'canal' => $transmission->canal,
            'type' => $message?->type,
            'operation' => $message?->operation,
            'destination_masquee' => $transmission->destination_masquee,
            'nombre_tentatives' => $transmission->nombre_tentatives,
            'prochaine_tentative_a' => $transmission->prochaine_tentative_a?->toIso8601String(),
            'niveau_acquittement' => $transmission->niveau_acquittement,
            'envoye_a' => $transmission->envoye_a?->toIso8601String(),
        ];
    }
}
