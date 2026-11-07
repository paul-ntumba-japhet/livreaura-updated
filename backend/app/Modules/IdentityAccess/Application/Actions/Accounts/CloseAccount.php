<?php

namespace App\Modules\IdentityAccess\Application\Actions\Accounts;

use App\Modules\IdentityAccess\Application\Actions\Messaging\RevokeOpenChallenges;
use App\Modules\IdentityAccess\Application\Actions\Sessions\RevokeAccountSessions;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\ChallengePurpose;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportExchange;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;

class CloseAccount
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private RevokeAccountSessions $revokeSessions,
        private RevokeOpenChallenges $revokeChallenges,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @param  array{demande_id: string, motif: string}  $input
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
            if (! ($this->hasPermission)($actor, 'cloturer_compte')) {
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
                    'Cette session ne peut pas cloturer ce compte.',
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

            $demande = $this->lockDemand($input['demande_id']);

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

            $unresolved = OrganizationMembership::query()
                ->where('compte_id', $target->id)
                ->where('principal', 1)
                ->where('statut', 'active')
                ->orderBy('organisation_id')
                ->get(['id', 'organisation_id']);

            if ($unresolved->isNotEmpty()) {
                throw new IdentityAccessException(
                    'representation_non_resolue',
                    'La representation principale doit etre transferee avant la cloture.',
                    409,
                    ['organisations' => $unresolved->pluck('organisation_id')->all()],
                );
            }

            $now = now();
            $previous = $target->statut->value;
            $target->statut = AccountStatus::Closed;
            $target->ferme_a = $now;
            $target->save();

            $event = new AccountEvent;
            $event->id = IdentityUlid::make();
            $event->compte_sujet_id = $target->id;
            $event->type = 'compte_acces_modifie';
            $event->date = $now;
            $event->ancien_etat = json_encode(['statut' => $previous], JSON_THROW_ON_ERROR);
            $event->nouvel_etat = json_encode([
                'statut' => AccountStatus::Closed->value,
                'ferme_a' => $now->toIso8601String(),
                'regle' => 'decision_humaine',
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
            $notice->contenu_prive = 'Le compte a ete cloture. Aucun ancien lien de recuperation ne restaure un acces normal.';
            $notice->date = $now;
            $notice->canal = 'backoffice';
            $notice->save();

            $this->closeDemand($demande, $actor, $input['motif'], $now, $correlationId);

            $revokedSessions = ($this->revokeSessions)($target->id, 'Cloture du compte.');
            $openChallenges = VerificationChallenge::query()
                ->where('compte_id', $target->id)
                ->whereNull('consomme_a')
                ->whereNull('revoque_a')
                ->count();

            foreach (ChallengePurpose::cases() as $purpose) {
                ($this->revokeChallenges)($target->id, $purpose);
            }

            $payload = [
                'http_status' => 200,
                'statut' => 'cloture',
                'prochaine_action' => 'conservation',
                'propagation' => 'en_cours',
                'compte' => [
                    'id' => $target->id,
                    'statut' => $target->statut->value,
                    'ferme_a' => $now->toIso8601String(),
                    'email_masque' => DestinationMasker::email($target->email_normalise),
                    'prenom' => $target->profile?->prenom,
                    'nom' => $target->profile?->nom,
                ],
                'demande' => [
                    'id' => $demande->id,
                    'reference' => $demande->reference,
                ],
                'sessions_revoquees' => $revokedSessions,
                'defis_revoques' => $openChallenges,
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'compte_acces_modifie',
                operation: 'A1-24',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $target->id,
                accountEventId: $event->id,
                demandeId: $demande->id,
            );

            if ($revokedSessions > 0) {
                $this->outbox->record(
                    idempotencyKey: 'a1-24:session:'.$idempotencyKey,
                    type: 'session_acces_modifie',
                    operation: 'A1-24',
                    correlation: $correlationId,
                    intentionFingerprint: SecretHasher::hmac($idempotencyKey.':sessions'),
                    payload: [
                        'compte_id' => $target->id,
                        'sessions_revoquees' => $revokedSessions,
                        'motif' => 'Cloture du compte.',
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
        $closed->motif = 'Cloture du compte consignee.';
        $closed->correlation = $correlationId;
        $closed->save();
    }
}
