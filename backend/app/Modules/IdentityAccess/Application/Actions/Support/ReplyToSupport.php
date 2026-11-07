<?php

namespace App\Modules\IdentityAccess\Application\Actions\Support;

use App\Modules\IdentityAccess\Application\Queries\GetSupportDemand;
use App\Modules\IdentityAccess\Application\Support\ResolveSupportDemandAccess;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportExchange;

class ReplyToSupport
{
    public function __construct(
        private CoordinationGuard $coordination,
        private ResolveSupportDemandAccess $resolveAccess,
        private GetSupportDemand $demandQuery,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @param  array{contenu: string, visibilite: string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $actor,
        string $demandKey,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $actor,
            $demandKey,
            $input,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
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
            $access = ($this->resolveAccess)($actor, $demandKey, true, true);

            if (! $access->canMutate) {
                throw new IdentityAccessException('etat_incompatible', 'Cette demande n est plus ouverte.', 409);
            }

            $visibility = $access->isClaimant() ? 'demandeur' : $input['visibilite'];

            if ($visibility === 'interne' && ! $access->isAgent()) {
                throw new IdentityAccessException(
                    'permission_insuffisante',
                    'Cette operation interne n est pas autorisee.',
                    403,
                );
            }

            $now = now();
            $exchange = new SupportExchange;
            $exchange->id = IdentityUlid::make();
            $exchange->demande_id = $access->demande->id;
            $exchange->auteur_compte_id = $actor->id;
            $exchange->origine = $access->isAgent() ? 'agent' : 'demandeur';
            $exchange->visibilite = $visibility;
            $exchange->contenu_prive = $input['contenu'];
            $exchange->date = $now;
            $exchange->canal = $access->isAgent() ? 'backoffice' : 'session';
            $exchange->save();

            $serialized = ($this->demandQuery)->serialize(
                $access->demande,
                $access->seeInternal,
                $access->canMutate,
                $access->isAgent(),
            );

            $payload = [
                'http_status' => 201,
                'statut' => 'echange_ajoute',
                'prochaine_action' => $access->demande->clos_a === null ? 'poursuivre_echange' : 'demande_close',
                'echange' => ($this->demandQuery)->serializeExchange($exchange),
                ...$serialized,
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'echange_support_ajoute',
                operation: 'echanges_support',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $actor->id,
                demandeId: $access->demande->id,
            );

            unset($payload['http_status']);

            return $payload;
        });
    }
}
