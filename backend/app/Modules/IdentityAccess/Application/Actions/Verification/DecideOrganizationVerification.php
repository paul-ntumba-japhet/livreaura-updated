<?php

namespace App\Modules\IdentityAccess\Application\Actions\Verification;

use App\Modules\IdentityAccess\Application\Queries\GetBackofficeVerificationDossier;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Application\Support\IsIndependentReviewer;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Messaging\IdentityOutboxPublisher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationControl;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDecision;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationEvent;

class DecideOrganizationVerification
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private IsIndependentReviewer $independent,
        private GetBackofficeVerificationDossier $reviewQuery,
        private IdentityOutboxPublisher $outbox,
    ) {}

    /**
     * @param  array{resultat: string, motif: string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $account,
        string $dossierId,
        array $input,
        string $idempotencyKey,
        string $intentionFingerprint,
        string $correlationId,
    ): array {
        return $this->coordination->withinIdentity(function () use (
            $account,
            $dossierId,
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

            if (! ($this->hasPermission)($account, 'controler_kyc') || ! ($this->hasPermission)($account, 'decider_kyc')) {
                throw new IdentityAccessException(
                    'permission_insuffisante',
                    'Cette operation interne n est pas autorisee.',
                    403,
                );
            }

            $dossier = VerificationDossier::query()
                ->with(['subject', 'serviceTask', 'decision', 'controls.requirement'])
                ->where('id', $dossierId)
                ->lockForUpdate()
                ->first();

            if ($dossier === null || $dossier->serviceTask === null) {
                throw new IdentityAccessException('dossier_inconnu', 'Ce dossier n est pas visible.', 404);
            }

            if ($dossier->niveau !== 'organisation_verifiee') {
                throw new IdentityAccessException(
                    'decision_commerciale_indisponible',
                    'La decision commerciale n est pas proposee ici.',
                    422,
                );
            }

            $task = ServiceTask::query()->where('id', $dossier->serviceTask->id)->lockForUpdate()->first();

            if ($dossier->decision !== null) {
                throw new IdentityAccessException(
                    'dossier_decide',
                    'Une decision est deja enregistree pour cette version.',
                    409,
                );
            }

            if (
                $dossier->statut !== 'under_review'
                || $task === null
                || $task->etat_travail !== 'en_cours'
                || $task->responsable_id !== $account->id
            ) {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'La decision exige un dossier en examen, affecte au responsable courant.',
                    409,
                );
            }

            if (! ($this->independent)($account, $dossier)) {
                throw new IdentityAccessException(
                    'conflit_interet',
                    'Ce dossier est lie a ce compte. Une reaffectation independante est necessaire.',
                    409,
                );
            }

            $result = $input['resultat'];
            $motif = trim($input['motif']);
            $pending = $dossier->controls->first(fn (VerificationControl $control): bool => $control->resultat === 'pending');

            if ($pending !== null) {
                throw new IdentityAccessException(
                    'controles_incomplets',
                    'Tous les controles doivent etre consignes avant la decision.',
                    422,
                );
            }

            if ($result === 'approved') {
                $blocking = $dossier->controls->first(function (VerificationControl $control): bool {
                    if ($control->resultat === 'passed') {
                        return false;
                    }

                    if ($control->resultat === 'not_applicable' && $control->requirement?->autorise_non_applicable) {
                        return false;
                    }

                    return true;
                });

                if ($blocking !== null) {
                    throw new IdentityAccessException(
                        'controles_insuffisants',
                        'L approbation exige que les controles obligatoires soient satisfaits.',
                        422,
                    );
                }
            }

            $now = now();
            $decision = new VerificationDecision;
            $decision->id = IdentityUlid::make();
            $decision->dossier_id = $dossier->id;
            $decision->decideur_id = $account->id;
            $decision->resultat = $result;
            $decision->motif = $motif;
            $decision->decide_a = $now;
            $decision->save();

            $dossier->statut = $result;
            $dossier->clos_a = $now;
            $dossier->save();

            $task->etat_travail = 'terminee';
            $task->clos_a = $now;
            $task->save();

            $taskEvent = new ServiceTaskEvent;
            $taskEvent->id = IdentityUlid::make();
            $taskEvent->tache_id = $task->id;
            $taskEvent->type = 'cloture';
            $taskEvent->date = $now;
            $taskEvent->auteur_id = $account->id;
            $taskEvent->ancien_etat = 'en_cours';
            $taskEvent->nouvel_etat = 'terminee';
            $taskEvent->motif = 'Decision enregistree. Tache terminee.';
            $taskEvent->correlation = $correlationId;
            $taskEvent->save();

            $capability = null;
            if ($result === 'approved') {
                $capability = VerificationCapability::query()
                    ->where('sujet_id', $dossier->sujet_id)
                    ->where('niveau', 'organisation_verifiee')
                    ->lockForUpdate()
                    ->first();

                if ($capability === null) {
                    $capability = new VerificationCapability;
                    $capability->id = IdentityUlid::make();
                    $capability->sujet_id = $dossier->sujet_id;
                    $capability->niveau = 'organisation_verifiee';
                    $capability->etat = 'valid';
                    $capability->obtenue_a = $now;
                }

                $capability->dossier_courant_id = $dossier->id;
                $capability->decision_courante_id = $decision->id;
                $capability->etat = 'valid';
                $capability->motif_restriction = null;
                $capability->valide_jusqua = $decision->valide_jusqua;
                $capability->save();
            }

            $event = new VerificationEvent;
            $event->id = IdentityUlid::make();
            $event->dossier_id = $dossier->id;
            $event->decision_id = $decision->id;
            $event->habilitation_id = $capability?->id;
            $event->type = 'decision';
            $event->date = $now;
            $event->ancien_etat = json_encode(['statut' => 'under_review'], JSON_THROW_ON_ERROR);
            $event->nouvel_etat = json_encode(['statut' => $result], JSON_THROW_ON_ERROR);
            $event->motif = $motif;
            $event->auteur_compte_id = $account->id;
            $event->correlation = $correlationId;
            $event->save();

            $fresh = $dossier->fresh([
                'subject.organization',
                'subject.profile',
                'documents',
                'controls.requirement',
                'fiscalProfile',
                'payoutAccount',
                'decision',
                'serviceTask',
            ]);

            $payload = [
                'http_status' => 200,
                'statut' => $result,
                'prochaine_action' => $result === 'approved' ? 'consulter' : 'nouvelle_version',
                ...($this->reviewQuery)->serialize($account, $fresh),
            ];

            $this->outbox->record(
                idempotencyKey: $idempotencyKey,
                type: 'dossier_kyc_decide',
                operation: 'A1-12',
                correlation: $correlationId,
                intentionFingerprint: $intentionFingerprint,
                payload: $payload,
                accountId: $dossier->deposant_id,
                dossierId: $dossier->id,
                kycEventId: $event->id,
            );

            unset($payload['http_status']);

            return $payload;
        });
    }
}
