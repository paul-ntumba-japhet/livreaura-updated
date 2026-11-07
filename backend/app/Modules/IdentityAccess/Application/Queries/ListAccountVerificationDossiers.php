<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Support\CanDepositVerification;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\KycSubject;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;

class ListAccountVerificationDossiers
{
    public function __construct(
        private CanDepositVerification $canDeposit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $account): array
    {
        $subjectIds = KycSubject::query()
            ->where('profil_compte_id', $account->id)
            ->orWhereIn('organisation_id', function ($query) use ($account): void {
                $query->select('organisation_id')
                    ->from('adhesions_organisation')
                    ->where('compte_id', $account->id)
                    ->where('statut', 'active');
            })
            ->pluck('id');

        $dossiers = VerificationDossier::query()
            ->with(['subject.organization'])
            ->whereIn('sujet_id', $subjectIds)
            ->whereIn('statut', ['draft', 'submitted', 'under_review', 'needs_fix', 'rejected', 'approved'])
            ->orderByDesc('cree_a')
            ->get()
            ->filter(fn (VerificationDossier $dossier): bool => $dossier->subject !== null && ($this->canDeposit)($account, $dossier->subject))
            ->values();

        $capabilities = VerificationCapability::query()
            ->whereIn('sujet_id', $dossiers->pluck('sujet_id')->unique()->all())
            ->get()
            ->keyBy(fn (VerificationCapability $capability): string => $capability->sujet_id.'|'.$capability->niveau);

        return [
            'dossiers_verification' => $dossiers->map(function (VerificationDossier $dossier) use ($capabilities): array {
                $capability = $capabilities->get($dossier->sujet_id.'|'.$dossier->niveau);

                return [
                    'id' => $dossier->id,
                    'numero' => $dossier->numero,
                    'version' => $dossier->version,
                    'niveau' => $dossier->niveau,
                    'statut' => $dossier->statut,
                    'sujet' => [
                        'type' => $dossier->subject?->isOrganization() ? 'organisation' : 'particulier',
                        'organisation_id' => $dossier->subject?->organisation_id,
                        'raison_sociale' => $dossier->subject?->organization?->raison_sociale,
                    ],
                    'habilitation' => $capability === null ? null : [
                        'id' => $capability->id,
                        'niveau' => $capability->niveau,
                        'etat' => $capability->etat,
                        'obtenue_a' => $capability->obtenue_a?->toIso8601String(),
                        'valide_jusqua' => $capability->valide_jusqua?->toIso8601String(),
                        'motif_restriction' => $capability->motif_restriction,
                    ],
                ];
            })->all(),
        ];
    }
}
