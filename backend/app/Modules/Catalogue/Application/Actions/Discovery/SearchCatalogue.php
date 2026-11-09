<?php

namespace App\Modules\Catalogue\Application\Actions\Discovery;

use App\Modules\Catalogue\Application\Queries\CatalogueCriteria;
use App\Modules\Catalogue\Application\Queries\CatalogueOfferQuery;
use App\Modules\Catalogue\Application\Queries\CatalogueTaxonomy;
use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class SearchCatalogue
{
    public function __construct(
        private CatalogueVisibility $visibility,
        private CatalogueOfferQuery $offers,
        private CatalogueTaxonomy $taxonomy,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function page(CatalogueCriteria $criteria): array
    {
        $filtered = $this->filtered($criteria);
        $total = (int) (clone $filtered)->count('oeuvre.oeuvre_id');
        $identifiers = $this->ordered(clone $filtered, $criteria)
            ->offset(($criteria->page - 1) * $criteria->taille)
            ->limit($criteria->taille)
            ->pluck('oeuvre_id')
            ->all();

        return [
            'oeuvres' => $this->cards($identifiers, $criteria),
            'total' => $total,
            'page' => $criteria->page,
            'taille' => $criteria->taille,
            'devise' => $criteria->devise,
            'tri' => $criteria->tri,
        ];
    }

    private function filtered(CatalogueCriteria $criteria): Builder
    {
        $query = DB::table('oeuvre')->selectRaw('oeuvre.oeuvre_id as oeuvre_id');
        $this->visibility->constrainPublicWork($query);

        if ($criteria->selectionId !== null) {
            $query->whereExists(function (Builder $selection) use ($criteria): void {
                $selection->selectRaw('1')
                    ->from('selection_oeuvre')
                    ->whereColumn('selection_oeuvre.oeuvre_id', 'oeuvre.oeuvre_id')
                    ->where('selection_oeuvre.selection_id', $criteria->selectionId);
            });
        }

        if ($criteria->texte !== null) {
            $this->applyText($query, $criteria->texte);
        }
        if ($criteria->categorieIds !== []) {
            $query->whereExists(function (Builder $link) use ($criteria): void {
                $link->selectRaw('1')
                    ->from('oeuvre_categorie')
                    ->whereColumn('oeuvre_categorie.oeuvre_id', 'oeuvre.oeuvre_id')
                    ->whereIn('oeuvre_categorie.categorie_id', $criteria->categorieIds);
            });
        }
        if ($criteria->langueId !== null) {
            $query->whereExists(function (Builder $link) use ($criteria): void {
                $link->selectRaw('1')
                    ->from('oeuvre_langue')
                    ->whereColumn('oeuvre_langue.oeuvre_id', 'oeuvre.oeuvre_id')
                    ->where('oeuvre_langue.langue_id', $criteria->langueId);
            });
        }
        if ($criteria->niveauId !== null) {
            $query->where('oeuvre.niveau_educatif_id', $criteria->niveauId);
        }
        if ($criteria->disponibilite !== null) {
            $query->where('oeuvre.availability', $criteria->disponibilite);
        }
        if ($criteria->badge !== null) {
            $query->where('oeuvre.badge', $criteria->badge);
        }
        if ($criteria->africain !== null) {
            $query->where('oeuvre.africain', $criteria->africain ? 1 : 0);
        }
        if ($criteria->auteurId !== null) {
            $query->whereExists(function (Builder $link) use ($criteria): void {
                $link->selectRaw('1')
                    ->from('oeuvre_auteur')
                    ->join('auteur', 'auteur.auteur_id', '=', 'oeuvre_auteur.auteur_id')
                    ->whereColumn('oeuvre_auteur.oeuvre_id', 'oeuvre.oeuvre_id')
                    ->where('oeuvre_auteur.auteur_id', $criteria->auteurId);
                $this->visibility->constrainVisibleAuthor($link);
            });
        }

        if ($criteria->contexte === CatalogueCriteria::FICHE) {
            $query->where(function (Builder $visible) use ($criteria): void {
                $this->offers->whereMatchingOfferExists($visible, $criteria);
                $visible->orWhere('oeuvre.availability', 'soon');
            });
        } elseif (! $criteria->allowsSoonWithoutOffer()) {
            $this->offers->whereMatchingOfferExists($query, $criteria);
        }

        return $query;
    }

    private function ordered(Builder $query, CatalogueCriteria $criteria): Builder
    {
        if ($criteria->tri === 'selection' && $criteria->selectionId !== null) {
            return $query->join('selection_oeuvre', function ($join) use ($criteria): void {
                $join->on('selection_oeuvre.oeuvre_id', '=', 'oeuvre.oeuvre_id')
                    ->where('selection_oeuvre.selection_id', '=', $criteria->selectionId);
            })->orderBy('selection_oeuvre.sort_order')
                ->orderBy('oeuvre.oeuvre_id');
        }

        if ($criteria->tri === 'price_asc' || $criteria->tri === 'price_desc') {
            $price = DB::query();
            $this->offers->matching($price, $criteria, 'oeuvre', true);
            $price->selectRaw('MIN(offre.prix)');

            return $query->selectSub($price, 'prix_reference')
                ->orderBy('prix_reference', $criteria->tri === 'price_asc' ? 'asc' : 'desc')
                ->orderBy('oeuvre.oeuvre_id');
        }

        return $query->orderByRaw('oeuvre.publie_a IS NULL')
            ->orderBy('oeuvre.publie_a', $criteria->tri === 'oldest' ? 'asc' : 'desc')
            ->orderBy('oeuvre.oeuvre_id');
    }

    private function applyText(Builder $query, string $text): void
    {
        $like = $this->likePattern($text);
        $categoryIds = $this->taxonomy->navigableCategoryIds();
        $query->where(function (Builder $where) use ($like, $categoryIds): void {
            $where->whereRaw('oeuvre.titre COLLATE utf8mb4_0900_ai_ci LIKE ? ESCAPE ?', [$like, '\\'])
                ->orWhereExists(function (Builder $author) use ($like): void {
                    $author->selectRaw('1')
                        ->from('oeuvre_auteur')
                        ->join('auteur', 'auteur.auteur_id', '=', 'oeuvre_auteur.auteur_id')
                        ->whereColumn('oeuvre_auteur.oeuvre_id', 'oeuvre.oeuvre_id')
                        ->whereRaw('auteur.nom_affiche COLLATE utf8mb4_0900_ai_ci LIKE ? ESCAPE ?', [$like, '\\']);
                    $this->visibility->constrainVisibleAuthor($author);
                })
                ->orWhereExists(function (Builder $edition) use ($like): void {
                    $edition->selectRaw('1')
                        ->from('edition')
                        ->join('oeuvre as edition_oeuvre', 'edition_oeuvre.oeuvre_id', '=', 'edition.oeuvre_id')
                        ->whereColumn('edition.oeuvre_id', 'oeuvre.oeuvre_id')
                        ->whereRaw('edition.isbn LIKE ? ESCAPE ?', [$like, '\\']);
                    $this->visibility->constrainVisibleEdition($edition, 'edition', 'edition_oeuvre');
                });
            if ($categoryIds !== []) {
                $where->orWhereExists(function (Builder $category) use ($like, $categoryIds): void {
                    $category->selectRaw('1')
                        ->from('oeuvre_categorie')
                        ->join('categorie', 'categorie.categorie_id', '=', 'oeuvre_categorie.categorie_id')
                        ->whereColumn('oeuvre_categorie.oeuvre_id', 'oeuvre.oeuvre_id')
                        ->whereIn('categorie.categorie_id', $categoryIds)
                        ->whereRaw('categorie.nom COLLATE utf8mb4_0900_ai_ci LIKE ? ESCAPE ?', [$like, '\\']);
                });
            }
        });
    }

    /**
     * @param  list<string>  $identifiers
     * @return list<array<string, mixed>>
     */
    private function cards(array $identifiers, CatalogueCriteria $criteria): array
    {
        if ($identifiers === []) {
            return [];
        }

        $works = DB::table('oeuvre')
            ->whereIn('oeuvre_id', $identifiers)
            ->get()
            ->keyBy('oeuvre_id');
        $authors = $this->authors($identifiers);
        $covers = $this->covers($identifiers);
        $offers = $this->offers->visibleOffers($identifiers);
        $matching = $criteria->hasOfferConstraint()
            ? array_fill_keys($this->offers->matchingOfferIds($identifiers, $criteria), true)
            : null;

        $cards = [];
        foreach ($identifiers as $identifier) {
            $work = $works->get($identifier);
            if ($work === null) {
                continue;
            }
            $workOffers = [];
            $reference = null;
            foreach ($offers as $offer) {
                if ($offer->oeuvre_id !== $identifier) {
                    continue;
                }
                $matches = $matching === null || isset($matching[$offer->offre_id]);
                if ($matches && $criteria->constrainsCurrency() && $offer->devise === $criteria->devise) {
                    if ($reference === null || (float) $offer->prix < (float) $reference) {
                        $reference = (string) $offer->prix;
                    }
                }
                $workOffers[] = [
                    'offre_id' => $offer->offre_id,
                    'format' => $offer->format_code,
                    'format_libelle' => $offer->format_libelle,
                    'modalite' => $offer->modalite_commerciale,
                    'prix' => $this->money($offer->prix),
                    'devise' => $offer->devise,
                    'etat' => $offer->condition,
                    'correspondante' => $matches,
                ];
            }

            $cards[] = [
                'oeuvre_id' => $work->oeuvre_id,
                'slug' => $work->slug,
                'titre' => $work->titre,
                'sous_titre' => $work->sous_titre,
                'disponibilite' => $work->availability,
                'badge' => $work->badge,
                'africain' => (int) $work->africain === 1,
                'mis_en_avant' => (int) $work->mis_en_avant === 1,
                'publie_a' => $this->instant($work->publie_a),
                'prix_reference' => $criteria->constrainsCurrency() ? $this->money($reference) : null,
                'auteurs' => $authors[$identifier] ?? [],
                'couverture' => $covers[$identifier] ?? null,
                'offres' => $workOffers,
            ];
        }

        return $cards;
    }

    /**
     * @param  list<string>  $identifiers
     * @return array<string, list<array<string, mixed>>>
     */
    private function authors(array $identifiers): array
    {
        $rows = DB::table('oeuvre_auteur')
            ->join('auteur', 'auteur.auteur_id', '=', 'oeuvre_auteur.auteur_id')
            ->whereIn('oeuvre_auteur.oeuvre_id', $identifiers)
            ->orderByRaw('oeuvre_auteur.ordre_affichage IS NULL')
            ->orderBy('oeuvre_auteur.ordre_affichage')
            ->orderBy('oeuvre_auteur.auteur_id');
        $this->visibility->constrainVisibleAuthor($rows);
        $grouped = [];
        foreach ($rows->get(['oeuvre_auteur.oeuvre_id', 'auteur.slug', 'auteur.nom_affiche', 'oeuvre_auteur.role']) as $row) {
            $grouped[$row->oeuvre_id][] = [
                'slug' => $row->slug,
                'nom' => $row->nom_affiche,
                'role' => $row->role,
            ];
        }

        return $grouped;
    }

    /**
     * @param  list<string>  $identifiers
     * @return array<string, array{media_catalogue_id: string, texte_alternatif: ?string}>
     */
    private function covers(array $identifiers): array
    {
        $covers = [];
        foreach ($identifiers as $identifier) {
            $query = DB::table('media_catalogue')
                ->where('media_catalogue.oeuvre_id', $identifier)
                ->whereNull('media_catalogue.edition_id')
                ->where('media_catalogue.type', 'couverture')
                ->orderBy('media_catalogue.ordre')
                ->orderBy('media_catalogue.media_catalogue_id');
            $this->visibility->constrainPublicMedia($query, $identifier);
            $cover = $query->first(['media_catalogue.media_catalogue_id', 'media_catalogue.texte_alternatif']);
            if ($cover === null) {
                continue;
            }
            $covers[$identifier] = [
                'media_catalogue_id' => $cover->media_catalogue_id,
                'texte_alternatif' => $cover->texte_alternatif,
            ];
        }

        return $covers;
    }

    private function likePattern(string $text): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text).'%';
    }

    private function money(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $text = (string) $value;
        if (! str_contains($text, '.')) {
            return $text.'.00';
        }
        [$whole, $fraction] = explode('.', $text, 2);
        $fraction = rtrim($fraction, '0');
        if ($fraction === '') {
            $fraction = '00';
        } elseif (strlen($fraction) === 1) {
            $fraction .= '0';
        }

        return $whole.'.'.$fraction;
    }

    private function instant(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $text = str_replace(' ', 'T', (string) $value);

        return str_ends_with($text, 'Z') ? $text : $text.'Z';
    }
}
