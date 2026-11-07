<?php

namespace App\Modules\IdentityAccess\Application\Actions\Support;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportExchange;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;

class VerifyClosureIdentity
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @param  array{compte_id: string, motif: string, methode_verification: string}  $input
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
            if (! ($this->hasPermission)($actor, 'traiter_support')) {
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

            $ids = collect([$actor->id, $input['compte_id']])->unique()->sort()->values();
            foreach ($ids as $id) {
                Account::query()->where('id', $id)->lockForUpdate()->first();
            }

            $demande = $this->lockDemand($demandKey);

            if ($demande === null || $demande->type !== 'cloture') {
                throw new IdentityAccessException('demande_inconnue', 'Cette demande n est pas visible.', 404);
            }

            if ($demande->clos_a !== null) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette demande n est plus ouverte.',
                    409,
                );
            }

            if ($demande->identite_etat === 'refusee') {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'L identite de cette demande a ete refusee.',
                    409,
                );
            }

            $target = Account::query()->where('id', $input['compte_id'])->lockForUpdate()->first();

            if ($target === null) {
                throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
            }

            if ($actor->id === $target->id) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette session ne peut pas verifier cette demande.',
                    409,
                );
            }

            if ($target->statut === AccountStatus::Closed) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Ce compte est deja cloture.',
                    409,
                );
            }

            if (
                $demande->identite_etat === 'verifiee'
                && $demande->compte_concerne_id !== null
                && $demande->compte_concerne_id !== $target->id
            ) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette demande ne concerne pas ce compte.',
                    409,
                );
            }

            $now = now();
            $demande->compte_concerne_id = $target->id;
            $demande->identite_etat = 'verifiee';
            $demande->identite_verifiee_par = $actor->id;
            $demande->identite_verifiee_a = $now;
            $demande->methode_verification = $input['methode_verification'];
            $demande->save();

            $task = ServiceTask::query()->where('demande_support_id', $demande->id)->lockForUpdate()->first();

            if ($task !== null && $task->etat_travail === 'a_affecter') {
                $previous = $task->etat_travail;
                $task->responsable_id = $actor->id;
                $task->etat_travail = 'en_cours';
                $task->affecte_a = $task->affecte_a ?? $now;
                $task->premiere_prise_a = $task->premiere_prise_a ?? $now;
                $task->save();

                $taken = new ServiceTaskEvent;
                $taken->id = IdentityUlid::make();
                $taken->tache_id = $task->id;
                $taken->type = 'prise_en_charge';
                $taken->date = $now;
                $taken->auteur_id = $actor->id;
                $taken->ancien_etat = $previous;
                $taken->nouvel_etat = 'en_cours';
                $taken->motif = 'Verification d identite de la demande de cloture.';
                $taken->correlation = $correlationId;
                $taken->save();
            }

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $target->id;
            $event->type = 'compte_acces_modifie';
            $event->date = $now;
            $event->nouvel_etat = json_encode([
                'demande_id' => $demande->id,
                'identite_etat' => 'verifiee',
                'regle' => 'verification_humaine',
            ], JSON_THROW_ON_ERROR);
            $event->motif = $input['motif'];
            $event->auteur_compte_id = $actor->id;
            $event->correlation = $correlationId;
            $event->save();

            $notice = new SupportExchange;
            $notice->id = IdentityUlid::make();
            $notice->demande_id = $demande->id;
            $notice->auteur_compte_id = $actor->id;
            $notice->origine = 'agent';
            $notice->visibilite = 'demandeur';
            $notice->contenu_prive = 'L identite de la demande de cloture a ete verifiee. La fermeture du compte n est pas encore decidee.';
            $notice->date = $now;
            $notice->canal = 'backoffice';
            $notice->save();

            $payload = [
                'http_status' => 200,
                'statut' => 'identite_verifiee',
                'prochaine_action' => 'cloture_possible',
                'demande' => [
                    'id' => $demande->id,
                    'reference' => $demande->reference,
                    'identite_etat' => $demande->identite_etat,
                ],
                'compte' => [
                    'id' => $target->id,
                    'statut' => $target->statut->value,
                    'email_masque' => DestinationMasker::email($target->email_normalise),
                ],
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'identite_cloture_verifiee',
                operation: 'cloture_identite',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $target->id,
                accountEventId: $event->id,
                demandeId: $demande->id,
            );

            unset($payload['http_status']);

            return $payload;
        });
    }

    private function lockDemand(string $demandKey): ?SupportRequest
    {
        $key = strtoupper($demandKey);

        if (IdentityUlid::isValid($key)) {
            return SupportRequest::query()->where('id', $key)->lockForUpdate()->first();
        }

        return SupportRequest::query()->where('reference', $demandKey)->lockForUpdate()->first();
    }
}
