<?php

namespace App\Modules\IdentityAccess\Application\Actions\Accounts;

use App\Modules\IdentityAccess\Application\Actions\Sessions\RevokeAccountSessions;
use App\Modules\IdentityAccess\Application\Queries\ListAccountConsentState;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportExchange;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;

class ReactivateAccount
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private ListAccountConsentState $consentState,
        private RevokeAccountSessions $revokeSessions,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @param  array{demande_id: string, motif: string, avis_securite: ?string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $actor,
        string $accountId,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $actor,
            $accountId,
            $input,
            $idempotencyKey,
            $intentionFingerprint,
            $correlationId,
        ): array {
            if (! ($this->hasPermission)($actor, 'reactiver_compte')) {
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

            $ids = collect([$actor->id, $accountId])->unique()->sort()->values();
            foreach ($ids as $id) {
                Account::query()->where('id', $id)->lockForUpdate()->first();
            }

            $target = Account::query()->with('profile')->where('id', $accountId)->lockForUpdate()->first();

            if ($target === null) {
                throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
            }

            if ($actor->id === $target->id) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette session ne peut pas reactiver ce compte.',
                    409,
                );
            }

            if ($target->statut === AccountStatus::Closed) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Un compte cloture n entre pas dans ce parcours.',
                    409,
                );
            }

            if ($target->statut !== AccountStatus::Suspended) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Seule une suspension en cours peut etre levee apres reexamen.',
                    409,
                );
            }

            $demande = $this->lockDemand($input['demande_id']);

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

            if ($demande->identite_etat !== 'verifiee') {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Le dossier n est pas encore verifie.',
                    409,
                );
            }

            if ($demande->compte_concerne_id !== $target->id) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Cette demande ne concerne pas ce compte.',
                    409,
                );
            }

            $requiresSecurityAdvice = $this->lastSuspensionRequiresSecurityAdvice($target);
            $avis = trim((string) ($input['avis_securite'] ?? ''));

            if ($requiresSecurityAdvice && $avis === '') {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Un avis de securite est requis avant de lever cette suspension.',
                    422,
                    ['avis_securite' => ['Avis de securite requis.']],
                );
            }

            $conditions = $this->evaluateConditions($target);
            $nextStatus = $conditions['email_verifie'] && $conditions['profil_complet'] && $conditions['textes_requis']
                ? AccountStatus::Active
                : AccountStatus::Pending;

            $now = now();
            $previous = $target->statut->value;
            $target->statut = $nextStatus;
            $target->save();

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $target->id;
            $event->type = 'compte_acces_modifie';
            $event->date = $now;
            $event->ancien_etat = json_encode(['statut' => $previous], JSON_THROW_ON_ERROR);
            $event->nouvel_etat = json_encode([
                'statut' => $nextStatus->value,
                'regle' => 'decision_humaine',
                'conditions' => $conditions,
                'avis_securite_consigne' => $avis !== '',
                'habilitation_kyc_restauree' => false,
            ], JSON_THROW_ON_ERROR);
            $event->motif = $input['motif'];
            $event->auteur_compte_id = $actor->id;
            $event->correlation = $correlationId;
            $event->save();

            if ($avis !== '') {
                $note = new SupportExchange;
                $note->id = IdentityUlid::make();
                $note->demande_id = $demande->id;
                $note->auteur_compte_id = $actor->id;
                $note->origine = 'agent';
                $note->visibilite = 'interne';
                $note->contenu_prive = $avis;
                $note->date = $now;
                $note->canal = 'backoffice';
                $note->save();
            }

            $notice = new SupportExchange;
            $notice->id = IdentityUlid::make();
            $notice->demande_id = $demande->id;
            $notice->auteur_compte_id = $actor->id;
            $notice->origine = 'agent';
            $notice->visibilite = 'demandeur';
            $notice->contenu_prive = $nextStatus === AccountStatus::Active
                ? 'La suspension a ete levee. Une nouvelle connexion est necessaire. Les habilitations de verification ne sont pas retablies automatiquement.'
                : 'La suspension a ete levee avec un acces encore limite. Confirmez e-mail, profil et textes requis, puis ouvrez une nouvelle connexion.';
            $notice->date = $now;
            $notice->canal = 'backoffice';
            $notice->save();

            $this->closeDemand($demande, $actor, $input['motif'], $now, $correlationId);

            $revoked = ($this->revokeSessions)($target->id, 'Reactivation du compte.');

            $payload = [
                'http_status' => 200,
                'statut' => 'reactivation',
                'prochaine_action' => 'connexion',
                'propagation' => 'en_cours',
                'compte' => [
                    'id' => $target->id,
                    'statut' => $target->statut->value,
                    'email_masque' => DestinationMasker::email($target->email_normalise),
                    'prenom' => $target->profile?->prenom,
                    'nom' => $target->profile?->nom,
                ],
                'demande' => [
                    'id' => $demande->id,
                    'reference' => $demande->reference,
                ],
                'sessions_revoquees' => $revoked,
                'conditions' => $conditions,
                'avis_securite_consigne' => $avis !== '',
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'compte_acces_modifie',
                operation: 'A1-23',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $target->id,
                accountEventId: $event->id,
                demandeId: $demande->id,
            );

            if ($revoked > 0) {
                $this->outbox->record(
                    idempotencyKey: 'a1-23:session:'.$idempotencyKey,
                    type: 'session_acces_modifie',
                    operation: 'A1-23',
                    correlation: $correlationId,
                    intentionFingerprint: SecretHasher::hmac($idempotencyKey.':sessions'),
                    payload: [
                        'compte_id' => $target->id,
                        'sessions_revoquees' => $revoked,
                        'motif' => 'Reactivation du compte.',
                    ],
                    accountId: $target->id,
                );
            }

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

    /**
     * @return array{email_verifie: bool, profil_complet: bool, textes_requis: bool}
     */
    private function evaluateConditions(Account $target): array
    {
        $profile = $target->profile;
        $pending = ($this->consentState)($target)['textes_en_attente'];

        return [
            'email_verifie' => $target->email_verifie_a !== null,
            'profil_complet' => $profile !== null
                && trim((string) $profile->prenom) !== ''
                && trim((string) $profile->nom) !== ''
                && trim((string) $profile->pays) !== '',
            'textes_requis' => $pending === [],
        ];
    }

    private function lastSuspensionRequiresSecurityAdvice(Account $target): bool
    {
        $events = AccountEvent::query()
            ->where('compte_sujet_id', $target->id)
            ->where('type', 'compte_acces_modifie')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        foreach ($events as $event) {
            $state = json_decode((string) $event->nouvel_etat, true);
            if (! is_array($state) || ($state['statut'] ?? '') !== AccountStatus::Suspended->value) {
                continue;
            }

            return (bool) preg_match('/(?<!hors )securit/i', (string) $event->motif);
        }

        return false;
    }

    private function closeDemand(
        SupportRequest $demande,
        Account $actor,
        string $motif,
        \DateTimeInterface $now,
        string $correlationId,
    ): void {
        $demande->conclusion = $motif;
        $demande->decide_par = $actor->id;
        $demande->clos_a = $now;
        $demande->save();

        $task = ServiceTask::query()->where('demande_support_id', $demande->id)->lockForUpdate()->first();

        if ($task === null || $task->etat_travail === 'terminee') {
            return;
        }

        $previous = $task->etat_travail;
        $task->responsable_id = $task->responsable_id ?? $actor->id;
        $task->etat_travail = 'terminee';
        $task->clos_a = $now;
        $task->save();

        $closed = new ServiceTaskEvent;
        $closed->id = IdentityUlid::make();
        $closed->tache_id = $task->id;
        $closed->type = 'cloture';
        $closed->date = $now;
        $closed->auteur_id = $actor->id;
        $closed->ancien_etat = $previous;
        $closed->nouvel_etat = 'terminee';
        $closed->motif = 'Reactivation du compte consignee.';
        $closed->correlation = $correlationId;
        $closed->save();
    }
}
