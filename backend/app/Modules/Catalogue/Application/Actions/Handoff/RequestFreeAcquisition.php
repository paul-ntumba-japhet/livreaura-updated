<?php

namespace App\Modules\Catalogue\Application\Actions\Handoff;

use App\Modules\Catalogue\Application\Handoff\CatalogueAuthorityGateway;
use App\Modules\Catalogue\Application\Support\CatalogueActor;
use App\Modules\Catalogue\Application\Support\CatalogueIntention;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Support\Facades\DB;

final class RequestFreeAcquisition
{
    public function __construct(
        private CoordinationGuard $guard,
        private CatalogueActor $actor,
        private CatalogueVisibility $visibility,
        private CatalogueIntention $intentions,
        private CatalogueAuthorityGateway $authority,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function request(Account $account, ?string $sessionId, string $offerId, ?string $clientKey, string $correlation): array
    {
        $actor = $this->guard->withinIdentity(function () use ($account, $sessionId): Account {
            return $this->actor->assertSession($account, $sessionId);
        });

        $offer = $this->offer($offerId);

        if (bccomp($this->price($offer->prix), '0', 4) !== 0) {
            throw CatalogueConsultationException::invalidInput('offre', 'Cette offre n’est pas gratuite.');
        }

        $key = $this->intentions->key('obtention', $actor->id, $offer->offre_id, $clientKey);

        return $this->authority->requestFreeAcquisition([
            'offre_id' => $offer->offre_id,
            'modalite_commerciale' => (string) $offer->modalite_commerciale,
            'devise' => (string) $offer->devise,
            'prix' => $this->price($offer->prix),
            'compte_id' => $actor->id,
            'correlation' => $correlation,
            'cle_idempotence' => $key,
        ]);
    }

    private function offer(string $offerId): object
    {
        $stored = DB::table('offre')->where('offre_id', $offerId)->first();

        if ($stored === null) {
            throw CatalogueConsultationException::absent();
        }

        if ($stored->modalite_commerciale !== 'achat') {
            throw CatalogueConsultationException::invalidInput('offre', 'Cette modalité n’est pas disponible.');
        }

        $visible = DB::table('offre')->where('offre.offre_id', $offerId);
        $this->visibility->constrainVisibleOffer($visible, 'offre_oeuvre');
        $offer = $visible->first([
            'offre.offre_id',
            'offre.prix',
            'offre.devise',
            'offre.modalite_commerciale',
        ]);

        if ($offer === null) {
            throw CatalogueConsultationException::absent();
        }

        return $offer;
    }

    private function price(mixed $value): string
    {
        $price = (string) $value;

        return str_contains($price, '.') ? $price : $price.'.00';
    }
}
