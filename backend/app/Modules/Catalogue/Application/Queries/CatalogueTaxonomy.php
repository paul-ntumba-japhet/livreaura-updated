<?php

namespace App\Modules\Catalogue\Application\Queries;

use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use Illuminate\Support\Facades\DB;

final class CatalogueTaxonomy
{
    /** @var array<string, object>|null */
    private ?array $categories = null;

    /** @var array<string, object>|null */
    private ?array $levels = null;

    /**
     * @return list<array<string, mixed>>
     */
    public function categoryTree(): array
    {
        return $this->categoryChildren(null);
    }

    /**
     * @return array{categorie_id: string, nom: string, slug: string, chemin: string}
     */
    public function categoryPage(string $value): array
    {
        $id = $this->resolveCategory($value, false);
        $node = $this->categories()[$id];

        return [
            'categorie_id' => $node->categorie_id,
            'nom' => $node->nom,
            'slug' => $node->slug,
            'chemin' => $this->categoryPath($id),
        ];
    }

    /**
     * @return list<string>
     */
    public function categoryIds(string $value, bool $descendants): array
    {
        $id = $this->resolveCategory($value, true);
        if (! $descendants) {
            return [$id];
        }

        return $this->descendantCategoryIds($id, [$id => true]);
    }

    public function languageId(string $value): string
    {
        $value = trim($value);
        $language = IdentityUlid::isValid($value)
            ? DB::table('langue')->where('langue_id', $value)->where('actif', 1)->first()
            : DB::table('langue')->where('code', $value)->where('actif', 1)->first();

        if ($language === null) {
            throw CatalogueConsultationException::invalid('langue', 'Cette langue n’est pas consultable.');
        }

        return $language->langue_id;
    }

    public function levelId(string $value): string
    {
        $value = trim($value);
        if (IdentityUlid::isValid($value)) {
            $id = isset($this->levels()[$value]) ? $value : null;
        } else {
            $matches = [];
            foreach ($this->navigableLevels() as $id => $level) {
                if ($level->slug === $value) {
                    $matches[] = $id;
                }
            }
            $id = count($matches) === 1 ? $matches[0] : null;
            if (count($matches) > 1) {
                throw CatalogueConsultationException::invalid('niveau', 'Ce niveau éducatif est ambigu.');
            }
        }

        if ($id === null || ! isset($this->navigableLevels()[$id])) {
            throw CatalogueConsultationException::invalid('niveau', 'Ce niveau éducatif n’est pas consultable.');
        }

        return $id;
    }

    public function levelIsNavigable(string $id): bool
    {
        return isset($this->navigableLevels()[$id]);
    }

    public function levelName(string $id): ?string
    {
        $level = $this->navigableLevels()[$id] ?? null;

        return $level === null ? null : $level->nom;
    }

    /**
     * @return list<array{code: string, nom: string}>
     */
    public function languageOptions(): array
    {
        $options = [];
        foreach (DB::table('langue')->where('actif', 1)->orderBy('nom')->orderBy('langue_id')->get(['code', 'nom']) as $language) {
            $options[] = [
                'code' => $language->code,
                'nom' => $language->nom,
            ];
        }

        return $options;
    }

    /**
     * @return list<array{slug: string, nom: string}>
     */
    public function levelOptions(): array
    {
        $options = [];
        foreach ($this->navigableLevels() as $level) {
            $options[] = [
                'slug' => $level->slug,
                'nom' => $level->nom,
                'sort_order' => (int) $level->sort_order,
                'niveau_educatif_id' => $level->niveau_educatif_id,
            ];
        }
        usort($options, function (array $left, array $right): int {
            return [$left['sort_order'], $left['niveau_educatif_id']] <=> [$right['sort_order'], $right['niveau_educatif_id']];
        });

        return array_map(fn (array $level): array => [
            'slug' => $level['slug'],
            'nom' => $level['nom'],
        ], $options);
    }

    /**
     * @param  list<string>  $ids
     * @return list<array{categorie_id: string, nom: string, chemin: string}>
     */
    public function categoriesByIds(array $ids): array
    {
        $items = [];
        foreach ($ids as $id) {
            $node = $this->categories()[$id] ?? null;
            if ($node === null || ! $this->categoryIsNavigable($id)) {
                continue;
            }
            $items[] = [
                'categorie_id' => $id,
                'nom' => $node->nom,
                'chemin' => $this->categoryPath($id),
            ];
        }

        usort($items, function (array $left, array $right): int {
            return [$left['chemin'], $left['categorie_id']] <=> [$right['chemin'], $right['categorie_id']];
        });

        return $items;
    }

    /**
     * @return list<string>
     */
    public function navigableCategoryIds(): array
    {
        return array_keys($this->navigableCategories());
    }

    private function resolveCategory(string $value, bool $asCriterion): string
    {
        $value = trim($value);
        if ($value === '') {
            $this->rejectCategory($asCriterion, 'Indiquez une catégorie.');
        }

        if (IdentityUlid::isValid($value)) {
            if (! $this->categoryIsNavigable($value)) {
                $this->rejectCategory($asCriterion, 'Cette catégorie n’est pas consultable.');
            }

            return $value;
        }

        if (! str_contains($value, '/')) {
            $roots = [];
            foreach ($this->navigableCategories() as $id => $category) {
                if ($category->parent_id === null && $category->slug === $value) {
                    $roots[] = $id;
                }
            }
            if (count($roots) === 1) {
                return $roots[0];
            }
            if (count($roots) > 1) {
                $this->rejectCategory($asCriterion, 'Ce chemin de catégorie est ambigu.');
            }
            $this->rejectCategory($asCriterion, 'Indiquez le chemin complet de la catégorie.');
        }

        $parentId = null;
        $resolved = null;
        foreach (explode('/', $value) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || str_contains($segment, '\\')) {
                $this->rejectCategory($asCriterion, 'Ce chemin de catégorie n’est pas recevable.');
            }
            $matches = [];
            foreach ($this->navigableCategories() as $id => $category) {
                if ($category->slug === $segment && $category->parent_id === $parentId) {
                    $matches[] = $id;
                }
            }
            if (count($matches) !== 1) {
                $this->rejectCategory(
                    $asCriterion,
                    count($matches) > 1
                        ? 'Ce chemin de catégorie est ambigu.'
                        : 'Cette catégorie n’est pas consultable.',
                );
            }
            $resolved = $matches[0];
            $parentId = $resolved;
        }

        return $resolved;
    }

    private function rejectCategory(bool $asCriterion, string $message): never
    {
        if ($asCriterion) {
            throw CatalogueConsultationException::invalid('categorie', $message);
        }

        throw CatalogueConsultationException::absent();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categoryChildren(?string $parentId): array
    {
        $children = [];
        foreach ($this->navigableCategories() as $id => $category) {
            if ($category->parent_id !== $parentId) {
                continue;
            }
            $children[] = [
                'categorie_id' => $id,
                'nom' => $category->nom,
                'slug' => $category->slug,
                'chemin' => $this->categoryPath($id),
                'enfants' => $this->categoryChildren($id),
            ];
        }

        return $children;
    }

    /**
     * @param  array<string, true>  $trail
     * @return list<string>
     */
    private function descendantCategoryIds(string $id, array $trail): array
    {
        $ids = [$id];
        foreach ($this->navigableCategories() as $childId => $category) {
            if ($category->parent_id !== $id || isset($trail[$childId])) {
                continue;
            }
            $ids = array_merge($ids, $this->descendantCategoryIds($childId, $trail + [$childId => true]));
        }

        return array_values(array_unique($ids));
    }

    private function categoryPath(string $id): string
    {
        $slugs = [];
        $seen = [];
        $current = $id;
        while ($current !== null) {
            if (isset($seen[$current])) {
                return '';
            }
            $seen[$current] = true;
            $node = $this->categories()[$current] ?? null;
            if ($node === null) {
                return '';
            }
            array_unshift($slugs, $node->slug);
            $current = $node->parent_id;
        }

        return implode('/', $slugs);
    }

    private function categoryIsNavigable(string $id): bool
    {
        return isset($this->navigableCategories()[$id]);
    }

    /**
     * @return array<string, object>
     */
    private function navigableCategories(): array
    {
        $navigable = [];
        foreach ($this->categories() as $id => $category) {
            if ($this->ancestorsActive($this->categories(), $id)) {
                $navigable[$id] = $category;
            }
        }

        uasort($navigable, function (object $left, object $right): int {
            return [(int) $left->sort_order, $left->categorie_id] <=> [(int) $right->sort_order, $right->categorie_id];
        });

        return $navigable;
    }

    /**
     * @return array<string, object>
     */
    private function navigableLevels(): array
    {
        $navigable = [];
        foreach ($this->levels() as $id => $level) {
            if ($this->ancestorsActive($this->levels(), $id)) {
                $navigable[$id] = $level;
            }
        }

        return $navigable;
    }

    /**
     * @param  array<string, object>  $nodes
     */
    private function ancestorsActive(array $nodes, string $id): bool
    {
        $seen = [];
        $current = $id;
        while ($current !== null) {
            if (isset($seen[$current])) {
                return false;
            }
            $seen[$current] = true;
            $node = $nodes[$current] ?? null;
            if ($node === null || (int) $node->actif !== 1) {
                return false;
            }
            $current = $node->parent_id !== null && $node->parent_id !== '' ? $node->parent_id : null;
        }

        return true;
    }

    /**
     * @return array<string, object>
     */
    private function categories(): array
    {
        if ($this->categories === null) {
            $this->categories = $this->loadTree('categorie', 'categorie_id');
        }

        return $this->categories;
    }

    /**
     * @return array<string, object>
     */
    private function levels(): array
    {
        if ($this->levels === null) {
            $this->levels = $this->loadTree('niveau_educatif', 'niveau_educatif_id');
        }

        return $this->levels;
    }

    /**
     * @return array<string, object>
     */
    private function loadTree(string $table, string $key): array
    {
        $nodes = [];
        $rows = DB::table($table)->orderBy('sort_order')->orderBy($key)->get();
        foreach ($rows as $row) {
            $row->parent_id = $row->parent_id !== null && $row->parent_id !== '' ? $row->parent_id : null;
            $nodes[$row->{$key}] = $row;
        }

        return $nodes;
    }
}
