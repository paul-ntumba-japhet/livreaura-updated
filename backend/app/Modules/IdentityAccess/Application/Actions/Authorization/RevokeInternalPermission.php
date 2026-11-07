<?php

namespace App\Modules\IdentityAccess\Application\Actions\Authorization;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Application\Support\InternalAttributionState;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;

class RevokeInternalPermission
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private IdentityOutboxPublisher $outbox,
        private InternalAttributionState $state,
    ) {}

    /**
     * @param  array{motif: string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $actor,
        string $attributionId,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $actor,
            $attributionId,
            $input,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
            if (! ($this->hasPermission)($actor, 'gerer_habilitations_internes')) {
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

            Account::query()->where('id', $actor->id)->lockForUpdate()->first();

            $attribution = InternalAttribution::query()
                ->with('permission')
                ->where('id', $attributionId)
                ->lockForUpdate()
                ->first();

            if ($attribution === null) {
                throw new IdentityAccessException('habilitation_inconnue', 'Cette habilitation n est pas visible.', 404);
            }

            Account::query()->where('id', $attribution->compte_id)->lockForUpdate()->first();

            if ($actor->id === $attribution->compte_id) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette session ne peut pas revoquer sa propre attribution.',
                    409,
                );
            }

            if ($attribution->revoque_a !== null) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette attribution est deja revoquee.',
                    409,
                );
            }

            $now = now();
            $attribution->revoque_a = $now;
            $attribution->save();

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $attribution->compte_id;
            $event->attribution_interne_id = $attribution->id;
            $event->type = 'permission_interne_modifiee';
            $event->date = $now;
            $event->nouvel_etat = json_encode([
                'permission' => $attribution->permission?->code,
                'action' => 'revocation',
            ], JSON_THROW_ON_ERROR);
            $event->motif = $input['motif'];
            $event->auteur_compte_id = $actor->id;
            $event->correlation = $correlationId;
            $event->save();

            $serialized = ($this->state)($attribution);
            $payload = [
                'http_status' => 200,
                'statut' => 'revoquee',
                'prochaine_action' => 'droits_courants',
                'compte_id' => $attribution->compte_id,
                'attribution' => $serialized,
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'permission_interne_modifiee',
                operation: 'permissions_internes',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $attribution->compte_id,
                accountEventId: $event->id,
            );

            unset($payload['http_status']);

            return $payload;
        });
    }
}
