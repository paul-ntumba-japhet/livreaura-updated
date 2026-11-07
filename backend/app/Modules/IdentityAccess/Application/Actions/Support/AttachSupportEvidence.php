<?php

namespace App\Modules\IdentityAccess\Application\Actions\Support;

use App\Modules\IdentityAccess\Application\Queries\GetSupportDemand;
use App\Modules\IdentityAccess\Application\Support\ResolveSupportDemandAccess;
use App\Modules\IdentityAccess\Domain\Contracts\PrivateEvidenceStore;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportEvidence;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportExchange;
use Illuminate\Http\UploadedFile;

class AttachSupportEvidence
{
    public function __construct(
        private CoordinationGuard $coordination,
        private ResolveSupportDemandAccess $resolveAccess,
        private PrivateEvidenceStore $evidence,
        private GetSupportDemand $demandQuery,
    ) {}

    /**
     * @param  array{type: string, acces: string, echange_id: ?string}  $input
     * @return array<string, mixed>
     */
    public function __invoke(
        Account $actor,
        string $demandKey,
        array $input,
        UploadedFile $file,
        string $correlationId,
    ): array {
        $stored = $this->evidence->store(
            (string) $file->get(),
            (string) $file->getMimeType(),
            (string) $file->getClientOriginalName(),
        );

        return $this->coordination->withinIdentityThenConservation(function () use (
            $actor,
            $demandKey,
            $input,
            $stored,
        ): array {
            Account::query()->where('id', $actor->id)->lockForUpdate()->first();
            $access = ($this->resolveAccess)($actor, $demandKey, true, true);

            if (! $access->canMutate) {
                throw new IdentityAccessException('etat_incompatible', 'Cette demande n est plus ouverte.', 409);
            }

            $acces = $access->isClaimant() ? 'demandeur' : $input['acces'];

            if ($acces === 'interne' && ! $access->isAgent()) {
                throw new IdentityAccessException(
                    'permission_insuffisante',
                    'Cette operation interne n est pas autorisee.',
                    403,
                );
            }

            $exchangeId = $input['echange_id'];

            if ($exchangeId !== null) {
                $exchange = SupportExchange::query()
                    ->where('id', $exchangeId)
                    ->where('demande_id', $access->demande->id)
                    ->lockForUpdate()
                    ->first();

                if ($exchange === null) {
                    throw new IdentityAccessException('demande_inconnue', 'Cette demande n est pas visible.', 404);
                }

                if ($exchange->visibilite === 'interne' && ! $access->seeInternal) {
                    throw new IdentityAccessException('demande_inconnue', 'Cette demande n est pas visible.', 404);
                }
            }

            if (! $this->evidence->exists($stored->reference) || $this->evidence->hashOf($stored->reference) !== $stored->sha256) {
                throw new IdentityAccessException(
                    'fichier_inaccessible',
                    'Le fichier n a pas pu etre conserve.',
                    503,
                );
            }

            $piece = new SupportEvidence;
            $piece->id = IdentityUlid::make();
            $piece->demande_id = $access->demande->id;
            $piece->echange_id = $exchangeId;
            $piece->deposant_id = $actor->id;
            $piece->type = $input['type'];
            $piece->reference_privee = $stored->reference;
            $piece->empreinte = $stored->sha256;
            $piece->depose_a = now();
            $piece->acces = $acces;
            $piece->save();

            $serialized = ($this->demandQuery)->serialize(
                $access->demande,
                $access->seeInternal,
                $access->canMutate,
                $access->isAgent(),
            );

            return [
                'statut' => 'piece_deposee',
                'prochaine_action' => 'poursuivre_echange',
                'piece' => ($this->demandQuery)->serializeEvidence($piece),
                ...$serialized,
            ];
        });
    }
}
