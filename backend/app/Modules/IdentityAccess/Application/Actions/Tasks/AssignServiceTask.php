<?php

namespace App\Modules\IdentityAccess\Application\Actions\Tasks;

use App\Modules\IdentityAccess\Application\Queries\GetBackofficeVerificationDossier;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Application\Support\IsIndependentReviewer;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTaskEvent;

class AssignServiceTask
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
    public function __invoke(Account $account, string $taskId, string $correlationId): array
    {
        return $this->coordination->withinIdentity(function () use ($account, $taskId, $correlationId): array {
            if (! ($this->hasPermission)($account, 'affecter_taches')) {
                throw new IdentityAccessException(
                    'permission_insuffisante',
                    'Cette operation interne n est pas autorisee.',
                    403,
                );
            }

            $task = ServiceTask::query()
                ->with('dossier.subject')
                ->where('id', $taskId)
                ->whereNotNull('dossier_kyc_id')
                ->lockForUpdate()
                ->first();

            if ($task === null || $task->dossier === null) {
                throw new IdentityAccessException('tache_inconnue', 'Cette tache n est pas visible.', 404);
            }

            if ($task->etat_travail === 'terminee') {
                throw new IdentityAccessException(
                    'tache_cloturee',
                    'Cette tache n est plus affectable.',
                    409,
                );
            }

            if (! ($this->independent)($account, $task->dossier)) {
                throw new IdentityAccessException(
                    'conflit_interet',
                    'Ce dossier est lie a ce compte. Une reaffectation independante est necessaire.',
                    409,
                );
            }

            if ($task->responsable_id === $account->id) {
                return [
                    'statut' => $task->dossier->statut,
                    'prochaine_action' => $task->dossier->statut === 'submitted' ? 'prendre_en_charge' : 'controler',
                    ...($this->reviewQuery)->serialize($account, $task->dossier->fresh([
                        'subject.organization',
                        'subject.profile',
                        'documents',
                        'controls.requirement',
                        'fiscalProfile',
                        'payoutAccount',
                        'decision',
                        'serviceTask',
                    ])),
                ];
            }

            $now = now();
            $previous = $task->responsable_id;
            $task->responsable_id = $account->id;
            $task->affecte_a = $now;
            $task->save();

            $event = new ServiceTaskEvent;
            $event->id = IdentityUlid::make();
            $event->tache_id = $task->id;
            $event->type = $previous === null ? 'affectation' : 'reaffectation';
            $event->date = $now;
            $event->auteur_id = $account->id;
            $event->ancien_responsable_id = $previous;
            $event->nouveau_responsable_id = $account->id;
            $event->ancien_etat = $task->etat_travail;
            $event->nouvel_etat = $task->etat_travail;
            $event->motif = $previous === null
                ? 'Affectation du dossier de verification.'
                : 'Reaffectation du dossier de verification.';
            $event->correlation = $correlationId;
            $event->save();

            $dossier = $task->dossier->fresh([
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
                'statut' => $dossier->statut,
                'prochaine_action' => $dossier->statut === 'submitted' ? 'prendre_en_charge' : 'controler',
                ...($this->reviewQuery)->serialize($account, $dossier),
            ];
        });
    }
}
