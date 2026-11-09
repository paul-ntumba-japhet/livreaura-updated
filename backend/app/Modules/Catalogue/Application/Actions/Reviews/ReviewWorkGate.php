<?php

namespace App\Modules\Catalogue\Application\Actions\Reviews;

use App\Modules\Catalogue\Application\Queries\CatalogueCriteria;
use App\Modules\Catalogue\Application\Queries\CatalogueOfferQuery;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use Illuminate\Support\Facades\DB;

final class ReviewWorkGate
{
    public function __construct(
        private CatalogueVisibility $visibility,
        private CatalogueOfferQuery $offers,
    ) {}

    public function visible(string $slug): object
    {
        $work = DB::table('oeuvre')->where('slug', $slug)->first();

        if ($work === null || ! $this->isVisible($work->oeuvre_id)) {
            throw CatalogueConsultationException::absent();
        }

        return $work;
    }

    public function acceptsReview(object $work): void
    {
        if ($work->availability === 'soon' && ! $this->hasPurchaseOffer($work->oeuvre_id)) {
            throw CatalogueConsultationException::forbidden(
                'avis_indisponible',
                'Les avis ne sont pas ouverts pour cette œuvre.',
            );
        }
    }

    private function isVisible(string $workId): bool
    {
        $query = DB::table('oeuvre')->where('oeuvre.oeuvre_id', $workId);
        $this->visibility->constrainPublicWork($query);
        $criteria = new CatalogueCriteria(
            null, [], null, null, null, null, null, 'USD', null, null, null, null, null, null, null, null,
            'recent', 1, 20, CatalogueCriteria::FICHE,
        );
        $query->where(function ($visible) use ($criteria): void {
            $this->offers->whereMatchingOfferExists($visible, $criteria);
            $visible->orWhere('oeuvre.availability', 'soon');
        });

        return $query->exists();
    }

    private function hasPurchaseOffer(string $workId): bool
    {
        $query = DB::table('oeuvre')->where('oeuvre.oeuvre_id', $workId);
        $criteria = new CatalogueCriteria(
            null, [], null, null, null, null, null, 'USD', null, null, null, null, null, null, null, null,
            'recent', 1, 20, CatalogueCriteria::FICHE,
        );
        $this->offers->whereMatchingOfferExists($query, $criteria);

        return $query->exists();
    }
}
