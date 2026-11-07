<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Support\CanDepositVerification;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\FiscalProfile;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\PayoutAccount;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationControl;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDocument;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationRequirement;

class GetVerificationDossier
{
    public function __construct(
        private CanDepositVerification $canDeposit,
        private ListApplicableVerificationRequirements $requirements,
        private EvaluateDossierCompleteness $completeness,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $account, string $dossierId): array
    {
        $dossier = VerificationDossier::query()
            ->with(['subject.organization', 'subject.profile', 'documents', 'controls.requirement', 'fiscalProfile', 'payoutAccount'])
            ->where('id', $dossierId)
            ->first();

        if ($dossier === null || $dossier->subject === null || ! ($this->canDeposit)($account, $dossier->subject)) {
            throw new IdentityAccessException(
                'dossier_inconnu',
                'Ce dossier n est pas visible.',
                404,
            );
        }

        return $this->serialize($dossier);
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(VerificationDossier $dossier): array
    {
        $subject = $dossier->subject;
        $requirements = $dossier->isDraft()
            ? ($this->requirements)($subject, $dossier->niveau, $this->countryOf($dossier))
            : $dossier->controls
                ->map(fn (VerificationControl $control): ?VerificationRequirement => $control->requirement)
                ->filter()
                ->values()
                ->all();

        $missing = $dossier->isDraft() ? ($this->completeness)($dossier, $requirements) : [];

        return [
            'id' => $dossier->id,
            'numero' => $dossier->numero,
            'version' => $dossier->version,
            'niveau' => $dossier->niveau,
            'statut' => $dossier->statut,
            'soumis_a' => $dossier->soumis_a?->toIso8601String(),
            'sujet' => [
                'id' => $subject?->id,
                'type' => $subject?->isOrganization() ? 'organisation' : 'particulier',
                'organisation_id' => $subject?->organisation_id,
                'raison_sociale' => $subject?->organization?->raison_sociale,
            ],
            'exigences' => ($this->requirements)->serializeMany($requirements),
            'pieces' => $dossier->documents
                ->map(fn (VerificationDocument $document): array => $this->serializeDocument($document))
                ->values()
                ->all(),
            'profil_fiscal' => $this->serializeFiscal($dossier->fiscalProfile),
            'compte_reversement' => $this->serializePayout($dossier->payoutAccount),
            'manques' => $missing,
            'complet' => $missing === [],
            'habilitation' => $this->serializeCapability($dossier),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeDocument(VerificationDocument $document): array
    {
        return [
            'id' => $document->id,
            'type_doc' => $document->type_doc,
            'statut_verification' => $document->statut_verification,
            'depose_a' => $document->depose_a?->toIso8601String(),
            'numero' => $document->numero,
            'autorite' => $document->autorite,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeFiscal(?FiscalProfile $profile): ?array
    {
        if ($profile === null) {
            return null;
        }

        return [
            'version' => $profile->version,
            'identifiant_fiscal' => $profile->identifiant_fiscal,
            'pays_fiscal' => $profile->pays_fiscal,
            'nom_legal' => $profile->nom_legal,
            'statut_controle' => $profile->statut_controle,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializePayout(?PayoutAccount $account): ?array
    {
        if ($account === null) {
            return null;
        }

        return [
            'version' => $account->version,
            'canal' => $account->canal,
            'prestataire' => $account->prestataire,
            'titulaire' => $account->titulaire,
            'pays' => $account->pays,
            'devise' => $account->devise,
            'affichage_masque' => $account->affichage_masque,
            'statut_controle' => $account->statut_controle,
        ];
    }

    private function countryOf(VerificationDossier $dossier): string
    {
        $subject = $dossier->subject;

        if ($subject?->isOrganization()) {
            return strtoupper((string) $subject->organization?->pays);
        }

        return strtoupper((string) $subject?->profile?->pays);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeCapability(VerificationDossier $dossier): ?array
    {
        $capability = VerificationCapability::query()
            ->where('sujet_id', $dossier->sujet_id)
            ->where('niveau', $dossier->niveau)
            ->first();

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
