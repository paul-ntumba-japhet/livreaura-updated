<?php

namespace App\Modules\IdentityAccess\Application\Actions\Verification;

use App\Modules\IdentityAccess\Application\Queries\GetVerificationDossier;
use App\Modules\IdentityAccess\Application\Support\CanDepositVerification;
use App\Modules\IdentityAccess\Domain\Contracts\PrivateEvidenceStore;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDocument;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationEvent;
use Illuminate\Http\UploadedFile;

class AttachVerificationEvidence
{
    public function __construct(
        private CoordinationGuard $coordination,
        private CanDepositVerification $canDeposit,
        private PrivateEvidenceStore $evidence,
        private GetVerificationDossier $dossierQuery,
    ) {}

    /**
     * @param  array{type_doc: string, numero: ?string, autorite: ?string, delivre_le: ?string, expire_le: ?string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $account,
        string $dossierId,
        array $input,
        UploadedFile $file,
        string $correlationId,
    ): array {
        $stored = $this->evidence->store(
            (string) $file->get(),
            (string) $file->getMimeType(),
            (string) $file->getClientOriginalName(),
        );

        return $this->coordination->withinIdentityThenConservation(function () use ($account, $dossierId, $input, $stored, $correlationId): array {
            $dossier = VerificationDossier::query()->where('id', $dossierId)->lockForUpdate()->first();

            if ($dossier === null) {
                throw new IdentityAccessException('dossier_inconnu', 'Ce dossier n est pas visible.', 404);
            }

            $dossier->load('subject');

            if ($dossier->subject === null || ! ($this->canDeposit)($account, $dossier->subject)) {
                throw new IdentityAccessException('dossier_inconnu', 'Ce dossier n est pas visible.', 404);
            }

            if (! $dossier->isDraft()) {
                throw new IdentityAccessException(
                    'dossier_fige',
                    'Ce dossier n est plus modifiable.',
                    409,
                );
            }

            if (! $this->evidence->exists($stored->reference) || $this->evidence->hashOf($stored->reference) !== $stored->sha256) {
                throw new IdentityAccessException(
                    'fichier_inaccessible',
                    'Le fichier n a pas pu etre conserve.',
                    503,
                );
            }

            $document = new VerificationDocument;
            $document->id = IdentityUlid::make();
            $document->dossier_id = $dossier->id;
            $document->deposant_id = $account->id;
            $document->type_doc = $input['type_doc'];
            $document->reference_privee = $stored->reference;
            $document->empreinte = $stored->sha256;
            $document->numero = $input['numero'];
            $document->autorite = $input['autorite'];
            $document->delivre_le = $input['delivre_le'];
            $document->expire_le = $input['expire_le'];
            $document->depose_a = now();
            $document->statut_verification = 'pending';
            $document->save();

            $event = new VerificationEvent;
            $event->id = IdentityUlid::make();
            $event->dossier_id = $dossier->id;
            $event->type = 'piece_deposee';
            $event->date = now();
            $event->nouvel_etat = json_encode([
                'document_id' => $document->id,
                'type_doc' => $document->type_doc,
            ], JSON_THROW_ON_ERROR);
            $event->motif = 'Depot d une piece privee, sans publication.';
            $event->origine_systeme = 'A1-11';
            $event->correlation = $correlationId;
            $event->save();

            $dossier->refresh()->load(['subject.organization', 'subject.profile', 'documents', 'controls.requirement', 'fiscalProfile', 'payoutAccount']);

            return [
                'statut' => 'draft',
                'prochaine_action' => 'completer_dossier',
                'piece' => ($this->dossierQuery)->serializeDocument($document),
                'dossier' => ($this->dossierQuery)->serialize($dossier),
            ];
        });
    }
}
