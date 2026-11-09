<?php

namespace App\Modules\Catalogue\Application\Actions\Discovery;

use App\Modules\Catalogue\Application\Queries\CatalogueCriteria;
use App\Modules\Catalogue\Application\Queries\CatalogueOfferQuery;
use App\Modules\Catalogue\Application\Queries\CatalogueTaxonomy;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ShowWork
{
    public function __construct(
        private CatalogueVisibility $visibility,
        private CatalogueOfferQuery $offers,
        private CatalogueTaxonomy $taxonomy,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function show(string $slug, Request $request): array
    {
        $work = DB::table('oeuvre')->where('slug', $slug)->first();
        if ($work === null || ! $this->isDirectlyVisible($work->oeuvre_id)) {
            throw CatalogueConsultationException::absent();
        }

        $reviewPage = $this->page($request->query('avis_page'), 'avis_page');
        $reviewSize = $this->size($request->query('avis_taille'), 'avis_taille');
        $editions = $this->editions($work->oeuvre_id);
        $editionIds = array_column($editions, 'edition_id');
        $offerRows = $this->offerRows($work->oeuvre_id);
        $reviews = $this->reviews($work->oeuvre_id, $reviewPage, $reviewSize);

        return [
            'oeuvre_id' => $work->oeuvre_id,
            'slug' => $work->slug,
            'titre' => $work->titre,
            'sous_titre' => $work->sous_titre,
            'resume' => $work->resume,
            'pages' => $work->pages !== null ? (int) $work->pages : null,
            'disponibilite' => $work->availability,
            'badge' => $work->badge,
            'africain' => (int) $work->africain === 1,
            'mis_en_avant' => (int) $work->mis_en_avant === 1,
            'publie_a' => $this->instant($work->publie_a),
            'note_moyenne' => $reviews['note_moyenne'],
            'avis_count' => $reviews['total'],
            'auteurs' => $this->authors($work->oeuvre_id),
            'categories' => $this->categories($work->oeuvre_id),
            'langues' => $this->languages($work->oeuvre_id, $work->langue_principale_id),
            'niveau' => $this->level($work->niveau_educatif_id),
            'collection' => $this->collection($work->collection_id),
            'editions' => $editions,
            'offres' => $offerRows,
            'medias' => $this->media($work->oeuvre_id, $editionIds),
            'avis' => [
                'total' => $reviews['total'],
                'page' => $reviewPage,
                'taille' => $reviewSize,
                'elements' => $reviews['elements'],
            ],
            'lecture' => [
                'etat' => 'verification_indisponible',
            ],
        ];
    }

    private function isDirectlyVisible(string $workId): bool
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

    /**
     * @return list<array<string, mixed>>
     */
    private function editions(string $workId): array
    {
        $query = DB::table('edition')
            ->join('oeuvre', 'oeuvre.oeuvre_id', '=', 'edition.oeuvre_id')
            ->leftJoin('editeur', function ($join): void {
                $join->on('editeur.editeur_id', '=', 'edition.editeur_id')
                    ->where('editeur.actif', '=', 1);
            })
            ->where('edition.oeuvre_id', $workId)
            ->select([
                'edition.edition_id',
                'edition.isbn',
                'edition.titre_edition',
                'edition.date_edition',
                'editeur.slug as editeur_slug',
                'editeur.nom as editeur_nom',
            ])
            ->orderBy('edition.edition_id');
        $this->visibility->constrainVisibleEdition($query, 'edition', 'oeuvre');

        return $query->get()->map(function (object $edition): array {
            return [
                'edition_id' => $edition->edition_id,
                'isbn' => $edition->isbn,
                'titre' => $edition->titre_edition,
                'date_edition' => $edition->date_edition,
                'editeur' => $edition->editeur_slug === null ? null : [
                    'slug' => $edition->editeur_slug,
                    'nom' => $edition->editeur_nom,
                ],
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function offerRows(string $workId): array
    {
        $rows = [];
        foreach ($this->offers->visibleOffers([$workId]) as $offer) {
            $rows[] = [
                'offre_id' => $offer->offre_id,
                'edition_id' => $offer->edition_id,
                'format' => $offer->format_code,
                'format_libelle' => $offer->format_libelle,
                'modalite' => $offer->modalite_commerciale,
                'prix' => $this->money($offer->prix),
                'devise' => $offer->devise,
                'etat' => $offer->condition,
                'correspondante' => true,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function authors(string $workId): array
    {
        $query = DB::table('oeuvre_auteur')
            ->join('auteur', 'auteur.auteur_id', '=', 'oeuvre_auteur.auteur_id')
            ->where('oeuvre_auteur.oeuvre_id', $workId)
            ->orderByRaw('oeuvre_auteur.ordre_affichage IS NULL')
            ->orderBy('oeuvre_auteur.ordre_affichage')
            ->orderBy('oeuvre_auteur.auteur_id');
        $this->visibility->constrainVisibleAuthor($query);

        return $query->get([
            'auteur.slug',
            'auteur.nom_affiche',
            'oeuvre_auteur.role',
            'oeuvre_auteur.ordre_affichage',
        ])->map(fn (object $row): array => [
            'slug' => $row->slug,
            'nom' => $row->nom_affiche,
            'role' => $row->role,
        ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categories(string $workId): array
    {
        $ids = DB::table('oeuvre_categorie')->where('oeuvre_id', $workId)->pluck('categorie_id')->all();

        return $this->taxonomy->categoriesByIds($ids);
    }

    /**
     * @return list<array{code: string, nom: string}>
     */
    private function languages(string $workId, mixed $primaryId): array
    {
        $rows = DB::table('langue')
            ->join('oeuvre_langue', 'oeuvre_langue.langue_id', '=', 'langue.langue_id')
            ->where('oeuvre_langue.oeuvre_id', $workId)
            ->where('langue.actif', 1)
            ->orderBy('langue.nom')
            ->get(['langue.langue_id', 'langue.code', 'langue.nom']);
        $languages = [];
        foreach ($rows as $row) {
            $languages[$row->langue_id] = ['code' => $row->code, 'nom' => $row->nom];
        }
        if (is_string($primaryId) && $primaryId !== '' && ! isset($languages[$primaryId])) {
            $primary = DB::table('langue')->where('langue_id', $primaryId)->where('actif', 1)->first();
            if ($primary !== null) {
                $languages[$primary->langue_id] = ['code' => $primary->code, 'nom' => $primary->nom];
            }
        }

        return array_values($languages);
    }

    /**
     * @return array{nom: string}|null
     */
    private function level(mixed $levelId): ?array
    {
        if (! is_string($levelId) || $levelId === '' || ! $this->taxonomy->levelIsNavigable($levelId)) {
            return null;
        }
        $name = $this->taxonomy->levelName($levelId);

        return $name === null ? null : ['nom' => $name];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function collection(mixed $collectionId): ?array
    {
        if (! is_string($collectionId) || $collectionId === '') {
            return null;
        }
        $collection = DB::table('collection')
            ->where('collection_id', $collectionId)
            ->where('actif', 1)
            ->first();
        if ($collection === null) {
            return null;
        }

        return [
            'slug' => $collection->slug,
            'nom' => $collection->nom,
            'kind' => $collection->kind,
            'prix_groupe' => null,
            'droit_groupe' => false,
        ];
    }

    /**
     * @param  list<string>  $editionIds
     * @return list<array<string, mixed>>
     */
    private function media(string $workId, array $editionIds): array
    {
        $query = DB::table('media_catalogue')
            ->where(function ($attachment) use ($workId, $editionIds): void {
                $attachment->where(function ($workMedia) use ($workId): void {
                    $workMedia->where('media_catalogue.oeuvre_id', $workId)
                        ->whereNull('media_catalogue.edition_id');
                });
                if ($editionIds !== []) {
                    $attachment->orWhere(function ($editionMedia) use ($editionIds): void {
                        $editionMedia->whereIn('media_catalogue.edition_id', $editionIds)
                            ->whereNull('media_catalogue.oeuvre_id');
                    });
                }
            })
            ->orderBy('media_catalogue.ordre')
            ->orderBy('media_catalogue.media_catalogue_id');
        $this->visibility->constrainPublicMedia($query, $workId);

        return $query->get([
            'media_catalogue.media_catalogue_id',
            'media_catalogue.type',
            'media_catalogue.texte_alternatif',
            'media_catalogue.ordre',
        ])->map(fn (object $media): array => [
            'media_catalogue_id' => $media->media_catalogue_id,
            'type' => $media->type,
            'texte_alternatif' => $media->texte_alternatif,
            'ordre' => $media->ordre !== null ? (int) $media->ordre : null,
        ])->all();
    }

    /**
     * @return array{total: int, note_moyenne: ?string, elements: list<array<string, mixed>>}
     */
    private function reviews(string $workId, int $page, int $size): array
    {
        $query = DB::table('avis')->where('avis.oeuvre_id', $workId);
        $this->visibility->constrainPublicReview($query, $workId);
        $total = (int) (clone $query)->count();
        $average = $total === 0 ? null : (clone $query)->avg('avis.note');
        $rows = (clone $query)
            ->leftJoin('profils_particuliers', 'profils_particuliers.compte_id', '=', 'avis.compte_id')
            ->orderByDesc('avis.soumis_a')
            ->orderBy('avis.avis_id')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get(['avis.note', 'avis.corps', 'avis.soumis_a', 'profils_particuliers.nom_plume']);

        return [
            'total' => $total,
            'note_moyenne' => $average === null ? null : number_format((float) $average, 1, '.', ''),
            'elements' => $rows->map(fn (object $review): array => [
                'note' => (int) $review->note,
                'corps' => $review->corps,
                'soumis_a' => $this->instant($review->soumis_a),
                'auteur' => is_string($review->nom_plume) && trim($review->nom_plume) !== ''
                    ? trim($review->nom_plume)
                    : 'Lecteur',
            ])->all(),
        ];
    }

    private function page(mixed $value, string $field): int
    {
        return $this->bounded($value, $field, 1, 10000);
    }

    private function size(mixed $value, string $field): int
    {
        $size = $this->bounded($value, $field, 10, 20);
        if ($value !== null && $value !== '' && $size > 20) {
            throw CatalogueConsultationException::invalid($field, 'Cette taille de page n’est pas recevable.');
        }

        return $size;
    }

    private function bounded(mixed $value, string $field, int $default, int $maximum): int
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (! is_string($value) || ! preg_match('/^[1-9]\d*$/', $value)) {
            throw CatalogueConsultationException::invalid($field, 'Cette valeur n’est pas recevable.');
        }
        $number = (int) $value;
        if ($number > $maximum) {
            throw CatalogueConsultationException::invalid($field, 'Cette valeur n’est pas recevable.');
        }

        return $number;
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
