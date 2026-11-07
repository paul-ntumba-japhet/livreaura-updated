<?php

namespace App\Modules\IdentityAccess\Application\Actions\Verification;

use App\Modules\IdentityAccess\Application\Queries\GetBackofficeVerificationDossier;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Application\Support\IsIndependentReviewer;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationEvent;

class StartVerificationReview
{
    public function __construct(
        private CoordinationGuard $coordination,
        private HasInternalPermission $hasPermission,
        private IsIndependentReviewer $independent,
        private GetBackofficeVerificationDossier $reviewQuery,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $account, string $dossierId, string $correlationId): array
    {
        return $this->coordination->withinIdentity(function () use ($account, $dossierId, $correlationId): array {
            if (! ($this->hasPermission)($account, 'controler_kyc')) {
                throw new IdentityAccessException(
                    'permission_insuffisante',
                    'Cette operation interne n est pas autorisee.',
                    403,
                );
            }

            $dossier = VerificationDossier::query()
                ->with(['subject', 'serviceTask'])
                ->where('id', $dossierId)
                ->lockForUpdate()
                ->first();

            if ($dossier === null || $dossier->serviceTask === null) {
                throw new IdentityAccessException('dossier_inconnu', 'Ce dossier n est pas visible.', 404);
            }

            $task = ServiceTask::query()->where('id', $dossier->serviceTask->id)->lockForUpdate()->first();

            if ($task === null) {
                throw new IdentityAccessException('tache_inconnue', 'Cette tache n est pas visible.', 404);
            }

            if (! ($this->independent)($account, $dossier)) {
                throw new IdentityAccessException(
                    'conflit_interet',
                    'Ce dossier est lie a ce compte. Une reaffectation independante est necessaire.',
                    409,
                );
            }

            if ($task->responsable_id !== $account->id) {
                throw new IdentityAccessException(
                    'affectation_requise',
                    'Le dossier doit etre affecte au responsable courant avant la prise en charge.',
                    409,
                );
            }

            if ($dossier->statut === 'under_review' && $task->etat_travail === 'en_cours') {
                return $this->payload($account, $dossier);
            }

            if ($dossier->statut !== 'submitted') {
                throw new IdentityAccessException(
                    'etat_incompatible',
                    'Ce dossier ne peut pas etre pris en charge dans cet etat.',
                    409,
                );
            }

            $now = now();
            $previousTaskState = $task->etat_travail;
            $dossier->statut = 'under_review';
            $dossier->save();

            $task->etat_travail = 'en_cours';
            if ($task->premiere_prise_a === null) {
                $task->premiere_prise_a = $now;
            }
            $task->save();

            $taskEvent = new ServiceTaskEvent;
            $taskEvent->id = IdentityUlid::make();
            $taskEvent->tache_id = $task->id;
            $taskEvent->type = 'prise';
            $taskEvent->date = $now;
            $taskEvent->auteur_id = $account->id;
            $taskEvent->ancien_etat = $previousTaskState;
            $taskEvent->nouvel_etat = 'en_cours';
            $taskEvent->motif = 'Prise en charge du dossier soumis.';
            $taskEvent->correlation = $correlationId;
            $taskEvent->save();

            $event = new VerificationEvent;
            $event->id = IdentityUlid::make();
            $event->dossier_id = $dossier->id;
            $event->type = 'prise_en_charge';
            $event->date = $now;
            $event->ancien_etat = json_encode(['statut' => 'submitted'], JSON_THROW_ON_ERROR);
            $event->nouvel_etat = json_encode(['statut' => 'under_review'], JSON_THROW_ON_ERROR);
            $event->motif = 'Passage en examen. Aucune decision n est enregistree.';
            $event->auteur_compte_id = $account->id;
            $event->correlation = $correlationId;
            $event->save();

            return $this->payload($account, $dossier);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Account $account, VerificationDossier $dossier): array
    {
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

        return [
            'statut' => $fresh->statut,
            'prochaine_action' => 'controler',
            ...($this->reviewQuery)->serialize($account, $fresh),
        ];
    }
}
