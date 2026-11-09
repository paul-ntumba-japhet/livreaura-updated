<?php

namespace App\Modules\Catalogue\Application\Queries;

use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CatalogueOfferQuery
{
    public function __construct(private CatalogueVisibility $visibility) {}

    public function whereMatchingOfferExists(Builder $query, CatalogueCriteria $criteria, string $workAlias = 'oeuvre'): void
    {
        $query->whereExists(function (Builder $offer) use ($criteria, $workAlias): void {
            $this->matching($offer, $criteria, $workAlias, true);
            $offer->selectRaw('1');
        });
    }

    /**
     * @param  list<string>  $workIds
     * @return list<string>
     */
    public function matchingOfferIds(array $workIds, CatalogueCriteria $criteria): array
    {
        if ($workIds === []) {
            return [];
        }

        $query = DB::table('offre')->whereIn('edition.oeuvre_id', $workIds);
        $this->matching($query, $criteria, 'offre_oeuvre', false);

        return $query->pluck('offre.offre_id')->all();
    }

    /**
     * @param  list<string>  $workIds
     * @return list<object>
     */
    public function visibleOffers(array $workIds): array
    {
        if ($workIds === []) {
            return [];
        }

        $query = DB::table('offre')
            ->whereIn('edition.oeuvre_id', $workIds)
            ->select([
                'offre.offre_id',
                'offre.prix',
                'offre.devise',
                'offre.modalite_commerciale',
                'offre.condition',
                'edition.oeuvre_id',
                'edition.edition_id',
                'format_technique.code as format_code',
                'format_technique.libelle as format_libelle',
            ])
            ->orderBy('offre.prix')
            ->orderBy('offre.devise')
            ->orderBy('offre.offre_id');
        $this->visibility->constrainVisibleOffer($query, 'offre_oeuvre');

        return $query->get()->all();
    }

    public function matching(Builder $offer, CatalogueCriteria $criteria, string $outerWorkAlias, bool $correlate): void
    {
        $this->visibility->constrainVisibleOffer($offer, $correlate ? $outerWorkAlias : 'offre_oeuvre');
        if (! $correlate) {
            $offer->whereColumn('edition.oeuvre_id', 'offre_oeuvre.oeuvre_id');
        }

        if ($criteria->formatCode !== null) {
            $offer->where('format_technique.code', $criteria->formatCode);
        }
        if ($criteria->etat !== null) {
            $offer->where('offre.condition', $criteria->etat);
        }
        if ($criteria->gratuite === true) {
            $offer->where('offre.prix', '=', 0);
        } elseif ($criteria->gratuite === false) {
            $offer->where('offre.prix', '>', 0);
        }
        if ($criteria->prixMin !== null) {
            $offer->where('offre.prix', '>=', $criteria->prixMin);
        }
        if ($criteria->prixMax !== null) {
            $offer->where('offre.prix', '<=', $criteria->prixMax);
        }
        if ($criteria->constrainsCurrency()) {
            $offer->where('offre.devise', $criteria->devise);
        }
        if ($criteria->editeurId !== null) {
            $offer->join('editeur', 'editeur.editeur_id', '=', 'edition.editeur_id')
                ->where('editeur.editeur_id', $criteria->editeurId)
                ->where('editeur.actif', 1);
        }
    }
}
