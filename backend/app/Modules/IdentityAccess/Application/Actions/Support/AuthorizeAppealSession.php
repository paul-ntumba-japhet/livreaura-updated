<?php

namespace App\Modules\IdentityAccess\Application\Actions\Support;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportExchange;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;

class AuthorizeAppealSession
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private CurrentServiceConfiguration $configurations,
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
            if (
                ! ($this->hasPermission)($actor, 'traiter_support')
                || ! ($this->hasPermission)($actor, 'autoriser_recours')
            ) {
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

            if ($demande === null || $demande->type !== 'recours_suspension') {
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
                    'Cette session ne peut pas autoriser ce recours.',
                    409,
                );
            }

            if ($target->statut !== AccountStatus::Suspended) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Une session de recours n est possible que pour un compte suspendu.',
                    409,
                );
            }

            $access = $this->configurations->find('A1_acces');
            $values = $this->configurations->values('A1_acces');

            if ($access === null) {
                throw new IdentityAccessException(
                    'service_indisponible',
                    'La politique d acces n est pas disponible.',
                    503,
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
                $taken->motif = 'Verification d identite du recours.';
                $taken->correlation = $correlationId;
                $taken->save();
            }

            $inactivity = (int) ($values['inactivite_secondes_limited'] ?? 900);
            $absolute = (int) ($values['absolue_secondes_limited'] ?? 3600);
            $placeholder = bin2hex(random_bytes(32));

            $session = new AuthenticationSession;
            $session->id = IdentityUlid::make();
            $session->compte_id = $target->id;
            $session->empreinte_reference = SecretHasher::hmac($placeholder);
            $session->portee = SessionAccessScope::SupportOnly;
            $session->statut = SessionStatus::Active;
            $session->ouverte_a = $now;
            $session->derniere_activite_a = $now;
            $session->expire_absolue_a = $now->copy()->addSeconds(max($absolute, 60));
            $session->expire_inactivite_a = $now->copy()->addSeconds(max($inactivity, 60));
            $session->demande_support_id = $demande->id;
            $session->autorise_par = $actor->id;
            $session->config_acces_id = $access->id;
            $session->save();

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $target->id;
            $event->type = 'session_acces_modifie';
            $event->date = $now;
            $event->nouvel_etat = json_encode([
                'session_id' => $session->id,
                'portee' => SessionAccessScope::SupportOnly->value,
                'demande_id' => $demande->id,
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
            $notice->contenu_prive = 'L identite du recours a ete verifiee. Une session de recours peut etre ouverte par une nouvelle connexion, sans jeton envoye par e-mail.';
            $notice->date = $now;
            $notice->canal = 'backoffice';
            $notice->save();

            $payload = [
                'http_status' => 200,
                'statut' => 'session_autorisee',
                'prochaine_action' => 'connexion_recours',
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
                'session' => [
                    'portee' => SessionAccessScope::SupportOnly->value,
                    'expire_absolue_a' => $session->expire_absolue_a?->toIso8601String(),
                ],
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'session_acces_modifie',
                operation: 'A1-25',
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
