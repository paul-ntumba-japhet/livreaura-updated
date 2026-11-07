<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Domain\Contracts\PrivateEvidenceStore;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDocument;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationRequirement;

class EvaluateDossierCompleteness
{
    public function __construct(
        private PrivateEvidenceStore $evidence,
    ) {}

    /**
     * @param  list<VerificationRequirement>  $requirements
     * @return list<array{code: string, volet: string, motif: string}>
     */
    public function __invoke(VerificationDossier $dossier, array $requirements): array
    {
        $missing = [];
        $documents = $dossier->documents;

        foreach ($requirements as $requirement) {
            if (! $requirement->obligatoire) {
                continue;
            }

            if ($requirement->requiresDocument()) {
                $match = $documents->first(function (VerificationDocument $document) use ($requirement): bool {
                    return $document->type_doc === $requirement->code
                        && $this->evidence->exists($document->reference_privee)
                        && $this->evidence->hashOf($document->reference_privee) === $document->empreinte;
                });

                if ($match === null) {
                    $missing[] = [
                        'code' => $requirement->code,
                        'volet' => $requirement->volet,
                        'motif' => 'piece_manquante',
                    ];
                }
            }

            if ($requirement->requiresFiscalProfile() && $dossier->profil_fiscal_id === null) {
                $missing[] = [
                    'code' => $requirement->code,
                    'volet' => $requirement->volet,
                    'motif' => 'profil_fiscal_manquant',
                ];
            }

            if ($requirement->requiresPayoutAccount() && $dossier->compte_reversement_id === null) {
                $missing[] = [
                    'code' => $requirement->code,
                    'volet' => $requirement->volet,
                    'motif' => 'compte_reversement_manquant',
                ];
            }
        }

        if ($requirements === []) {
            $missing[] = [
                'code' => 'exigences',
                'volet' => 'referentiel',
                'motif' => 'exigences_indisponibles',
            ];
        }

        return $missing;
    }
}
