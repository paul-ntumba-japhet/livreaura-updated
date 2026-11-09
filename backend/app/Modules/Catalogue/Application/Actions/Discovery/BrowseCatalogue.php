<?php

namespace App\Modules\Catalogue\Application\Actions\Discovery;

use App\Modules\Catalogue\Application\Queries\CatalogueCriteriaFactory;
use App\Modules\Catalogue\Application\Queries\CatalogueTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class BrowseCatalogue
{
    public function __construct(
        private CatalogueTaxonomy $taxonomy,
        private CatalogueCriteriaFactory $criteria,
        private SearchCatalogue $search,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function categories(): array
    {
        return [
            'categories' => $this->taxonomy->categoryTree(),
        ];
    }

    /**
     * @return array{langues: list<array{code: string, nom: string}>}
     */
    public function languages(): array
    {
        return [
            'langues' => $this->taxonomy->languageOptions(),
        ];
    }

    /**
     * @return array{niveaux: list<array{slug: string, nom: string}>}
     */
    public function levels(): array
    {
        return [
            'niveaux' => $this->taxonomy->levelOptions(),
        ];
    }

    /**
     * @return array{editeurs: list<array{slug: string, nom: string}>}
     */
    public function publishers(): array
    {
        $publishers = [];
        foreach (DB::table('editeur')->where('actif', 1)->orderBy('nom')->orderBy('editeur_id')->get(['slug', 'nom']) as $publisher) {
            $publishers[] = [
                'slug' => $publisher->slug,
                'nom' => $publisher->nom,
            ];
        }

        return [
            'editeurs' => $publishers,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function category(string $category, Request $request): array
    {
        $resolved = $this->criteria->fromCategory($category, $request);

        return [
            'categorie' => $resolved['categorie'],
            'resultats' => $this->search->page($resolved['criteres']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function selection(string $code, Request $request): array
    {
        $resolved = $this->criteria->fromSelection($code, $request);

        return [
            'selection' => $resolved['selection'],
            'resultats' => $this->search->page($resolved['criteres']),
        ];
    }
}
