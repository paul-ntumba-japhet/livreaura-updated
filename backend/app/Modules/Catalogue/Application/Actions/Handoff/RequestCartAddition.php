<?php

namespace App\Modules\Catalogue\Application\Actions\Handoff;

use App\Modules\Catalogue\Application\Handoff\CatalogueAuthorityGateway;
use App\Modules\Catalogue\Application\Support\CatalogueIntention;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use Illuminate\Support\Facades\DB;

final class RequestCartAddition
{
    public function __construct(
        private CatalogueVisibility $visibility,
        private CatalogueIntention $intentions,
        private CatalogueAuthorityGateway $authority,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function request(string $offerId, array $input, ?string $clientKey, string $correlation): array
    {
        $quantity = $input['quantite'] ?? null;

        if (! is_int($quantity) || $quantity < 1 || $quantity > 2147483647) {
            throw CatalogueConsultationException::invalidInput('quantite', 'La quantité doit être un entier strictement positif.');
        }

        $offer = $this->offer($offerId);
        $key = $this->intentions->key('panier', 'visiteur', $offer->offre_id, $clientKey);

        return $this->authority->requestCart([
            'offre_id' => $offer->offre_id,
            'quantite' => $quantity,
            'modalite_commerciale' => (string) $offer->modalite_commerciale,
            'devise' => (string) $offer->devise,
            'prix' => $this->price($offer->prix),
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
