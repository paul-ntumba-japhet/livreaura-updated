<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Support\CanAccessKycBackoffice;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Application\Support\IsIndependentReviewer;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;

class ListBackofficeServiceTasks
{
    public function __construct(
        private CanAccessKycBackoffice $canAccess,
        private HasInternalPermission $hasPermission,
        private IsIndependentReviewer $independent,
        private GetVerificationDossier $dossierQuery,
        private GetBackofficeVerificationDossier $reviewQuery,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $account): array
    {
        if (! ($this->canAccess)($account)) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $tasks = ServiceTask::query()
            ->with([
                'dossier.subject.organization',
                'dossier.subject.profile',
                'dossier.documents',
                'dossier.controls.requirement',
                'dossier.fiscalProfile',
                'dossier.payoutAccount',
                'dossier.decision',
            ])
            ->whereNotNull('dossier_kyc_id')
            ->where('etat_travail', '!=', 'terminee')
            ->orderBy('echeance_prise_a')
            ->orderBy('id')
            ->get();

        return [
            'taches' => $tasks
                ->filter(fn (ServiceTask $task): bool => $task->dossier !== null && $task->dossier->statut !== 'draft')
                ->map(function (ServiceTask $task) use ($account): array {
                    $dossier = $task->dossier;
                    $independent = $dossier !== null && ($this->independent)($account, $dossier);

                    return [
                        'tache' => ($this->reviewQuery)->serializeTask($task),
                        'dossier' => ($this->dossierQuery)->serialize($dossier),
                        'conflit_interet' => ! $independent,
                        'capacites' => [
                            'consulter' => true,
                            'affecter' => ($this->hasPermission)($account, 'affecter_taches') && $independent,
                            'prendre_en_charge' => ($this->hasPermission)($account, 'controler_kyc')
                                && $independent
                                && $task->responsable_id === $account->id
                                && $dossier?->statut === 'submitted',
                        ],
                    ];
                })
                ->values()
                ->all(),
            'capacites' => [
                'affecter' => ($this->hasPermission)($account, 'affecter_taches'),
                'controler' => ($this->hasPermission)($account, 'controler_kyc'),
                'decider' => ($this->hasPermission)($account, 'decider_kyc')
                    || ($this->hasPermission)($account, 'decider_kyc_commercial'),
            ],
        ];
    }
}
