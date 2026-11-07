<?php

namespace App\Modules\IdentityAccess\Application\Actions\Authorization;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Application\Support\InternalAttributionState;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalPermission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use Illuminate\Support\Carbon;

class GrantInternalPermission
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private IdentityOutboxPublisher $outbox,
        private InternalAttributionState $state,
    ) {}

    /**
     * @param  array{compte_id: string, permission: string, motif: string, expire_a: ?string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $actor,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $actor,
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

            $targetId = $input['compte_id'];
            $ids = collect([$actor->id, $targetId])->unique()->sort()->values();
            foreach ($ids as $id) {
                Account::query()->where('id', $id)->lockForUpdate()->first();
            }

            $target = Account::query()->where('id', $targetId)->lockForUpdate()->first();

            if ($target === null) {
                throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
            }

            if ($actor->id === $target->id) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette session ne peut pas s attribuer une permission.',
                    409,
                );
            }

            if ($target->statut !== AccountStatus::Active) {
                throw new IdentityAccessException(
                    'compte_ineligible',
                    'Le compte indique n est pas eligible a cette operation.',
                    422,
                );
            }

            $permission = InternalPermission::query()->where('code', $input['permission'])->first();

            if ($permission === null) {
                throw new IdentityAccessException('permission_inconnue', 'Cette permission n est pas visible.', 404);
            }

            $expiresAt = $this->parseDeadline($input['expire_a']);
            $now = now();

            $current = InternalAttribution::query()
                ->where('compte_id', $target->id)
                ->where('permission_id', $permission->id)
                ->whereNull('revoque_a')
                ->where(function ($query) use ($now): void {
                    $query->whereNull('expire_a')->orWhere('expire_a', '>', $now);
                })
                ->lockForUpdate()
                ->first();

            if ($current !== null) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Une attribution courante existe deja pour cette permission.',
                    409,
                );
            }

            $attribution = new InternalAttribution;
            $attribution->id = IdentityUlid::make();
            $attribution->compte_id = $target->id;
            $attribution->permission_id = $permission->id;
            $attribution->attribue_par = $actor->id;
            $attribution->attribue_a = $now;
            $attribution->expire_a = $expiresAt;
            $attribution->motif = $input['motif'];
            $attribution->save();
            $attribution->setRelation('permission', $permission);

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $target->id;
            $event->attribution_interne_id = $attribution->id;
            $event->type = 'permission_interne_modifiee';
            $event->date = $now;
            $event->nouvel_etat = json_encode([
                'permission' => $permission->code,
                'action' => 'attribution',
                'expire_a' => $expiresAt?->toIso8601String(),
            ], JSON_THROW_ON_ERROR);
            $event->motif = $input['motif'];
            $event->auteur_compte_id = $actor->id;
            $event->correlation = $correlationId;
            $event->save();

            $serialized = ($this->state)($attribution);
            $payload = [
                'http_status' => 201,
                'statut' => 'attribuee',
                'prochaine_action' => 'droits_courants',
                'compte_id' => $target->id,
                'attribution' => $serialized,
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'permission_interne_modifiee',
                operation: 'permissions_internes',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $target->id,
                accountEventId: $event->id,
            );

            unset($payload['http_status']);

            return $payload;
        });
    }

    private function parseDeadline(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $expiresAt = Carbon::parse($value);
        } catch (\Throwable) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'L echeance est invalide.',
                422,
                ['expire_a' => ['Date invalide.']],
            );
        }

        if ($expiresAt->lte(now())) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'L echeance doit etre posterieure a maintenant.',
                422,
                ['expire_a' => ['Echeance passee.']],
            );
        }

        return $expiresAt;
    }
}
