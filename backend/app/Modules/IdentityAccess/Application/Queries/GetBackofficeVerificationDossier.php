<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Support\CanAccessKycBackoffice;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Application\Support\IsIndependentReviewer;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationControl;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDecision;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;

class GetBackofficeVerificationDossier
{
    public function __construct(
        private CanAccessKycBackoffice $canAccess,
        private HasInternalPermission $hasPermission,
        private IsIndependentReviewer $independent,
        private GetVerificationDossier $dossierQuery,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $account, string $dossierId): array
    {
        if (! ($this->canAccess)($account)) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $dossier = VerificationDossier::query()
            ->with([
                'subject.organization',
                'subject.profile',
                'documents',
                'controls.requirement',
                'fiscalProfile',
                'payoutAccount',
                'decision',
                'serviceTask',
            ])
            ->where('id', $dossierId)
            ->where('statut', '!=', 'draft')
            ->first();

        if ($dossier === null || $dossier->subject === null || $dossier->serviceTask === null) {
            throw new IdentityAccessException('dossier_inconnu', 'Ce dossier n est pas visible.', 404);
        }

        return $this->serialize($account, $dossier);
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(Account $account, VerificationDossier $dossier): array
    {
        $task = $dossier->serviceTask;
        $independent = ($this->independent)($account, $dossier);
        $assignedToSelf = $task !== null && $task->responsable_id === $account->id;
        $canAssign = ($this->hasPermission)($account, 'affecter_taches') && $task !== null && $task->etat_travail !== 'terminee';
        $canControl = ($this->hasPermission)($account, 'controler_kyc')
            && $independent
            && $assignedToSelf
            && $dossier->statut === 'under_review'
            && $task?->etat_travail === 'en_cours'
            && $dossier->decision === null;
        $canDecideOrganization = ($this->hasPermission)($account, 'decider_kyc')
            && ($this->hasPermission)($account, 'controler_kyc')
            && $independent
            && $assignedToSelf
            && $dossier->statut === 'under_review'
            && $dossier->niveau === 'organisation_verifiee'
            && $dossier->decision === null;
        $canDecideCommercial = ($this->hasPermission)($account, 'decider_kyc_commercial')
            && ($this->hasPermission)($account, 'controler_kyc')
            && $independent
            && $assignedToSelf
            && $dossier->statut === 'under_review'
            && $dossier->niveau === 'partenaire_commercial'
            && $dossier->decision === null;
        $canDecide = $canDecideOrganization || $canDecideCommercial;
        $canTakeCharge = ($this->hasPermission)($account, 'controler_kyc')
            && $independent
            && $assignedToSelf
            && $dossier->statut === 'submitted'
            && in_array($task?->etat_travail, ['a_affecter', 'en_cours'], true);

        $capability = VerificationCapability::query()
            ->where('sujet_id', $dossier->sujet_id)
            ->where('niveau', $dossier->niveau)
            ->first();
        $canRestrict = ($this->hasPermission)($account, 'restreindre_habilitation')
            && $capability !== null
            && ! in_array($capability->etat, ['expired', 'revoked'], true);

        return [
            'dossier' => ($this->dossierQuery)->serialize($dossier),
            'controles' => $dossier->controls
                ->map(fn (VerificationControl $control): array => $this->serializeControl($control))
                ->values()
                ->all(),
            'tache' => $task === null ? null : $this->serializeTask($task),
            'decision' => $this->serializeDecision($dossier->decision),
            'habilitation' => $this->serializeCapability($capability),
            'conflit_interet' => ! $independent,
            'niveau_decision' => match ($dossier->niveau) {
                'organisation_verifiee' => 'organisation_verifiee',
                'partenaire_commercial' => 'partenaire_commercial',
                default => 'hors_perimetre',
            },
            'capacites' => [
                'affecter' => $canAssign && $independent,
                'prendre_en_charge' => $canTakeCharge,
                'controler' => $canControl,
                'decider' => $canDecide,
                'restreindre' => $canRestrict,
                'consulter_pieces' => ($this->hasPermission)($account, 'controler_kyc') && $independent,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeTask(ServiceTask $task): array
    {
        return [
            'id' => $task->id,
            'etat_travail' => $task->etat_travail,
            'priorite' => $task->priorite,
            'motif_priorite' => $task->motif_priorite,
            'responsable_id' => $task->responsable_id,
            'cree_a' => $task->cree_a?->toIso8601String(),
            'affecte_a' => $task->affecte_a?->toIso8601String(),
            'premiere_prise_a' => $task->premiere_prise_a?->toIso8601String(),
            'echeance_prise_a' => $task->echeance_prise_a?->toIso8601String(),
            'echeance_reponse_a' => $task->echeance_reponse_a?->toIso8601String(),
            'clos_a' => $task->clos_a?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeControl(VerificationControl $control): array
    {
        return [
            'id' => $control->id,
            'resultat' => $control->resultat,
            'observation' => $control->observation,
            'justification_non_applicable' => $control->justification_non_applicable,
            'controle_a' => $control->controle_a?->toIso8601String(),
            'exigence' => $control->requirement === null ? null : [
                'id' => $control->requirement->id,
                'code' => $control->requirement->code,
                'volet' => $control->requirement->volet,
                'obligatoire' => (bool) $control->requirement->obligatoire,
                'autorise_non_applicable' => (bool) $control->requirement->autorise_non_applicable,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeDecision(?VerificationDecision $decision): ?array
    {
        if ($decision === null) {
            return null;
        }

        return [
            'id' => $decision->id,
            'resultat' => $decision->resultat,
            'motif' => $decision->motif,
            'decide_a' => $decision->decide_a?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeCapability(?VerificationCapability $capability): ?array
    {
        if ($capability === null) {
            return null;
        }

        return [
            'id' => $capability->id,
            'niveau' => $capability->niveau,
            'etat' => $capability->etat,
            'obtenue_a' => $capability->obtenue_a?->toIso8601String(),
            'valide_jusqua' => $capability->valide_jusqua?->toIso8601String(),
            'motif_restriction' => $capability->motif_restriction,
        ];
    }
}
