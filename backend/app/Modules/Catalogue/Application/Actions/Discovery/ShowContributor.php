<?php

namespace App\Modules\Catalogue\Application\Actions\Discovery;

use App\Modules\Catalogue\Application\Queries\CatalogueCriteria;
use App\Modules\Catalogue\Application\Queries\CatalogueCriteriaFactory;
use App\Modules\Catalogue\Application\Queries\CatalogueOfferQuery;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ShowContributor
{
    public function __construct(
        private CatalogueVisibility $visibility,
        private CatalogueCriteriaFactory $criteria,
        private SearchCatalogue $search,
        private CatalogueOfferQuery $offers,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function authors(Request $request): array
    {
        $page = $this->page($request->query('page'));
        $size = $this->size($request->query('taille'));
        $tagId = $this->tag($request->query('tag'));
        $query = DB::table('auteur')->select('auteur.auteur_id');
        $this->visibility->constrainVisibleAuthor($query);
        if ($tagId !== null) {
            $query->whereExists(function ($link) use ($tagId): void {
                $link->selectRaw('1')
                    ->from('auteur_tag')
                    ->whereColumn('auteur_tag.auteur_id', 'auteur.auteur_id')
                    ->where('auteur_tag.tag_auteur_id', $tagId);
            });
        }
        $total = (int) (clone $query)->count('auteur.auteur_id');
        $identifiers = $query->orderBy('auteur.nom_affiche')
            ->orderBy('auteur.auteur_id')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->pluck('auteur_id')
            ->all();

        return [
            'auteurs' => $this->authorCards($identifiers),
            'total' => $total,
            'page' => $page,
            'taille' => $size,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function author(string $slug, Request $request): array
    {
        $author = $this->visibleAuthor($slug);
        if ($author === null) {
            throw CatalogueConsultationException::absent();
        }
        $criteria = $this->criteria->forBibliography($author->auteur_id, $request);

        return [
            'auteur' => $this->authorProfile($author),
            'bibliographie' => $this->search->page($criteria),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publisher(string $slug, Request $request): array
    {
        if (! preg_match('/^[^\/\s]{1,191}$/u', $slug)) {
            throw CatalogueConsultationException::absent();
        }
        $publisher = DB::table('editeur')->where('slug', $slug)->where('actif', 1)->first();
        if ($publisher === null) {
            throw CatalogueConsultationException::absent();
        }
        $editionPage = $this->page($request->query('page'));
        $size = $this->size($request->query('taille'));

        return [
            'editeur' => [
                'slug' => $publisher->slug,
                'nom' => $publisher->nom,
                'presentation' => $publisher->presentation,
                'pays' => $publisher->pays,
                'mis_en_avant' => (int) $publisher->mis_en_avant === 1,
            ],
            'editions' => $this->editions($publisher->editeur_id, $editionPage, $size),
            'collections' => $this->collections($publisher->editeur_id),
        ];
    }

    private function visibleAuthor(string $slug): ?object
    {
        $query = DB::table('auteur')->where('auteur.slug', $slug);
        $this->visibility->constrainVisibleAuthor($query);

        return $query->first();
    }

    /**
     * @param  list<string>  $identifiers
     * @return list<array<string, mixed>>
     */
    private function authorCards(array $identifiers): array
    {
        if ($identifiers === []) {
            return [];
        }
        $authors = DB::table('auteur')->whereIn('auteur_id', $identifiers)->get()->keyBy('auteur_id');
        $tags = $this->tags($identifiers);
        $cards = [];
        foreach ($identifiers as $identifier) {
            $author = $authors->get($identifier);
            if ($author === null) {
                continue;
            }
            $cards[] = $this->authorProfile($author, $tags[$identifier] ?? []);
        }

        return $cards;
    }

    /**
     * @param  list<array{slug: string, nom: string}>  $tags
     * @return array<string, mixed>
     */
    private function authorProfile(object $author, ?array $tags = null): array
    {
        return [
            'slug' => $author->slug,
            'nom' => $author->nom_affiche,
            'biographie' => $author->bio,
            'pays' => $author->pays,
            'role_affiche' => $author->role_affiche,
            'verified' => (int) $author->verified === 1,
            'mis_en_avant' => (int) $author->mis_en_avant === 1,
            'tags' => $tags ?? $this->tags([$author->auteur_id])[$author->auteur_id] ?? [],
        ];
    }

    /**
     * @param  list<string>  $authorIds
     * @return array<string, list<array{slug: string, nom: string}>>
     */
    private function tags(array $authorIds): array
    {
        $rows = DB::table('auteur_tag')
            ->join('tag_auteur', 'tag_auteur.tag_auteur_id', '=', 'auteur_tag.tag_auteur_id')
            ->whereIn('auteur_tag.auteur_id', $authorIds)
            ->orderBy('tag_auteur.nom')
            ->get(['auteur_tag.auteur_id', 'tag_auteur.slug', 'tag_auteur.nom']);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->auteur_id][] = [
                'slug' => $row->slug,
                'nom' => $row->nom,
            ];
        }

        return $grouped;
    }

    private function tag(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value)) {
            throw CatalogueConsultationException::invalid('tag', 'Ce mot-clé n’est pas consultable.');
        }
        $tag = DB::table('tag_auteur')->where('slug', $value)->first();
        if ($tag === null) {
            throw CatalogueConsultationException::invalid('tag', 'Ce mot-clé n’est pas consultable.');
        }

        return $tag->tag_auteur_id;
    }

    /**
     * @return array<string, mixed>
     */
    private function editions(string $publisherId, int $page, int $size): array
    {
        $query = DB::table('edition')
            ->join('oeuvre', 'oeuvre.oeuvre_id', '=', 'edition.oeuvre_id')
            ->where('edition.editeur_id', $publisherId)
            ->select('edition.edition_id');
        $this->visibility->constrainVisibleEdition($query, 'edition', 'oeuvre');
        $total = (int) (clone $query)->count('edition.edition_id');
        $identifiers = $query->orderBy('edition.edition_id')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->pluck('edition_id')
            ->all();
        if ($identifiers === []) {
            return ['total' => $total, 'page' => $page, 'taille' => $size, 'elements' => []];
        }

        $rows = DB::table('edition')
            ->join('oeuvre', 'oeuvre.oeuvre_id', '=', 'edition.oeuvre_id')
            ->whereIn('edition.edition_id', $identifiers)
            ->get([
                'edition.edition_id',
                'edition.isbn',
                'edition.titre_edition',
                'oeuvre.oeuvre_id',
                'oeuvre.slug',
                'oeuvre.titre',
            ])
            ->keyBy('edition_id');
        $visibleWorks = $this->bibliographyWorks($rows->pluck('oeuvre_id')->all());
        $elements = [];
        foreach ($identifiers as $identifier) {
            $row = $rows->get($identifier);
            if ($row === null) {
                continue;
            }
            $work = isset($visibleWorks[$row->oeuvre_id]) ? [
                'slug' => $row->slug,
                'titre' => $row->titre,
            ] : null;
            $elements[] = [
                'edition_id' => $row->edition_id,
                'isbn' => $row->isbn,
                'titre' => $row->titre_edition,
                'oeuvre' => $work,
            ];
        }

        return [
            'total' => $total,
            'page' => $page,
            'taille' => $size,
            'elements' => $elements,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collections(string $publisherId): array
    {
        $collections = DB::table('collection')
            ->where('editeur_id', $publisherId)
            ->where('actif', 1)
            ->orderBy('nom')
            ->orderBy('collection_id')
            ->get();
        $items = [];
        foreach ($collections as $collection) {
            $works = DB::table('oeuvre')
                ->where('collection_id', $collection->collection_id)
                ->select(['oeuvre.oeuvre_id', 'oeuvre.slug', 'oeuvre.titre', 'oeuvre.publie_a']);
            $this->visibility->constrainPublicWork($works);
            $this->offers->whereMatchingOfferExists($works, $this->bibliographyCriteria());
            $total = (int) (clone $works)->count('oeuvre.oeuvre_id');
            $rows = $works->orderByRaw('oeuvre.publie_a IS NULL')
                ->orderByDesc('oeuvre.publie_a')
                ->orderBy('oeuvre.oeuvre_id')
                ->limit(20)
                ->get();
            $items[] = [
                'slug' => $collection->slug,
                'nom' => $collection->nom,
                'kind' => $collection->kind,
                'total' => $total,
                'oeuvres' => $rows->map(fn (object $work): array => [
                    'slug' => $work->slug,
                    'titre' => $work->titre,
                ])->all(),
            ];
        }

        return $items;
    }

    /**
     * @param  list<string>  $workIds
     * @return array<string, true>
     */
    private function bibliographyWorks(array $workIds): array
    {
        if ($workIds === []) {
            return [];
        }
        $query = DB::table('oeuvre')->whereIn('oeuvre.oeuvre_id', $workIds)->select('oeuvre.oeuvre_id');
        $this->visibility->constrainPublicWork($query);
        $this->offers->whereMatchingOfferExists($query, $this->bibliographyCriteria());

        return array_fill_keys($query->pluck('oeuvre_id')->all(), true);
    }

    private function bibliographyCriteria(): CatalogueCriteria
    {
        return new CatalogueCriteria(
            null, [], null, null, null, null, null, 'USD', null, null, null, null, null, null, null, null,
            'recent', 1, 20, CatalogueCriteria::BIBLIOGRAPHIE,
        );
    }

    private function page(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 1;
        }
        if (! is_string($value) || ! preg_match('/^[1-9]\d*$/', $value) || (int) $value > 10000) {
            throw CatalogueConsultationException::invalid('page', 'Cette page n’est pas recevable.');
        }

        return (int) $value;
    }

    private function size(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 20;
        }
        if (! is_string($value) || ! preg_match('/^[1-9]\d*$/', $value) || (int) $value > 50) {
            throw CatalogueConsultationException::invalid('taille', 'Cette taille de page n’est pas recevable.');
        }

        return (int) $value;
    }
}
