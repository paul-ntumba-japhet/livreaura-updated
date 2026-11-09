<?php

namespace App\Modules\Catalogue\Application\Queries;

use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

final class CatalogueCriteriaFactory
{
    public function __construct(
        private CatalogueTaxonomy $taxonomy,
        private CatalogueVisibility $visibility,
    ) {}

    public function fromSearch(Request $request): CatalogueCriteria
    {
        $resolved = $this->resolveSelection($this->input($request->query()));
        $criteria = $this->build(
            $resolved['input'],
            $resolved['base'],
            false,
            $resolved['context'],
            $resolved['selection_id'],
        );
        if ($resolved['context'] === CatalogueCriteria::A_PARAITRE) {
            return $this->withAvailability($criteria, 'soon', CatalogueCriteria::A_PARAITRE);
        }

        return $criteria;
    }

    public function fromSelection(string $code, Request $request): array
    {
        $selection = $this->activeSelection($code);
        $base = [];
        $selectionId = null;
        $context = CatalogueCriteria::GENERAL;
        $input = $this->input($request->query());
        unset($input['selection']);

        if ($selection->code === 'a_paraitre') {
            $context = CatalogueCriteria::A_PARAITRE;
            $base = $this->document($selection->parametres);
            $base['disponibilite'] = 'soon';
        } elseif ($selection->code === 'tendances' || $selection->regle_ou_manuelle === 'manuelle') {
            $selectionId = $selection->selection_id;
            if ($this->present($request->query('tri')) && $request->query('tri') !== 'selection') {
                throw CatalogueConsultationException::invalid(
                    'tri',
                    'Cette sélection conserve son ordre manuel.',
                );
            }
        } elseif ($selection->regle_ou_manuelle === 'filtre') {
            $base = $this->document($selection->parametres);
        } else {
            throw CatalogueConsultationException::invalid('selection', 'Cette sélection ne peut pas être consultée.');
        }

        $criteria = $this->build($input, $base, true, $context, $selectionId);
        if ($selection->code === 'a_paraitre') {
            $criteria = $this->withAvailability($criteria, 'soon', CatalogueCriteria::A_PARAITRE);
        }

        return [
            'selection' => [
                'code' => $selection->code,
                'libelle' => $selection->libelle,
                'mode' => $selection->code === 'tendances' || $selection->regle_ou_manuelle === 'manuelle'
                    ? 'manuelle'
                    : 'filtre',
            ],
            'criteres' => $criteria,
        ];
    }

    public function fromCategory(string $category, Request $request): array
    {
        $page = $this->taxonomy->categoryPage($category);
        $input = $this->input($request->query());
        unset($input['categorie']);
        $resolved = $this->resolveSelection($input);
        $descendants = $this->flag($resolved['input']['descendance'] ?? null, 'descendance') === true;
        $base = $resolved['base'] ?? [];
        $base['categorie_ids'] = $this->taxonomy->categoryIds($category, $descendants);
        $criteria = $this->build(
            $resolved['input'],
            $base,
            false,
            $resolved['context'],
            $resolved['selection_id'],
        );
        if ($resolved['context'] === CatalogueCriteria::A_PARAITRE) {
            $criteria = $this->withAvailability($criteria, 'soon', CatalogueCriteria::A_PARAITRE);
        }

        return [
            'categorie' => $page,
            'criteres' => $criteria,
        ];
    }

    public function forBibliography(string $authorId, Request $request): CatalogueCriteria
    {
        $input = $this->input($request->query());
        $input['page'] = $request->query('page', 1);
        $input['taille'] = $request->query('taille', 20);

        return $this->build($input, [], false, CatalogueCriteria::BIBLIOGRAPHIE, null, $authorId);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{input: array<string, mixed>, base: array<string, mixed>|null, context: string, selection_id: string|null}
     */
    private function resolveSelection(array $input): array
    {
        $context = CatalogueCriteria::GENERAL;
        $base = null;
        $selectionId = null;

        if ($this->present($input['selection'] ?? null)) {
            if (! is_string($input['selection'])) {
                throw CatalogueConsultationException::absent();
            }
            $selection = $this->activeSelection($input['selection']);
            unset($input['selection']);
            if ($selection->code === 'a_paraitre') {
                $context = CatalogueCriteria::A_PARAITRE;
                $base = $this->document($selection->parametres);
                $base['disponibilite'] = 'soon';
            } elseif ($selection->code === 'tendances' || $selection->regle_ou_manuelle === 'manuelle') {
                $selectionId = $selection->selection_id;
            } elseif ($selection->regle_ou_manuelle === 'filtre') {
                $base = $this->document($selection->parametres);
            } else {
                throw CatalogueConsultationException::invalid('selection', 'Cette sélection ne peut pas être consultée.');
            }
        }

        return [
            'input' => $input,
            'base' => $base,
            'context' => $context,
            'selection_id' => $selectionId,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>|null  $base
     */
    private function build(
        array $input,
        ?array $base,
        bool $dedicatedManual,
        string $context = CatalogueCriteria::GENERAL,
        ?string $selectionId = null,
        ?string $forcedAuthorId = null,
    ): CatalogueCriteria {
        $source = $base === null ? $input : array_replace($base, $this->withoutEmpty($input));
        $descendants = $this->flag($source['descendance'] ?? null, 'descendance') === true;
        if (isset($base['categorie_ids']) && is_array($base['categorie_ids'])) {
            $categoryIds = $base['categorie_ids'];
            if ($this->present($input['categorie'] ?? null)) {
                $categoryIds = array_values(array_intersect(
                    $categoryIds,
                    $this->categories($input['categorie'], $descendants),
                ));
                if ($categoryIds === []) {
                    $categoryIds = ['00000000000000000000000000'];
                }
            }
        } else {
            $categoryIds = $this->categories($source['categorie'] ?? null, $descendants);
        }

        $tri = $this->sort($source['tri'] ?? null, $selectionId, $dedicatedManual);
        if ($context === CatalogueCriteria::A_PARAITRE) {
            $source['disponibilite'] = 'soon';
        }

        return new CatalogueCriteria(
            texte: $this->text($source['texte'] ?? null),
            categorieIds: $categoryIds,
            formatCode: $this->format($source['format'] ?? null),
            langueId: $this->optionalResolved($source['langue'] ?? null, fn (string $value): string => $this->taxonomy->languageId($value)),
            niveauId: $this->optionalResolved($source['niveau'] ?? null, fn (string $value): string => $this->taxonomy->levelId($value)),
            prixMin: $this->money($source['prix_min'] ?? null, 'prix_min'),
            prixMax: $this->money($source['prix_max'] ?? null, 'prix_max', $source['prix_min'] ?? null),
            devise: $this->currency($source['devise'] ?? null),
            gratuite: $this->flag($source['gratuite'] ?? null, 'gratuite'),
            etat: $this->enum($source['etat'] ?? null, 'etat', ['none', 'new', 'used']),
            disponibilite: $this->availability($source['disponibilite'] ?? null),
            badge: $this->badge($source['badge'] ?? null),
            africain: $this->flag($source['africain'] ?? null, 'africain'),
            auteurId: $forcedAuthorId ?? $this->author($source['auteur'] ?? null),
            editeurId: $this->publisher($source['editeur'] ?? null),
            selectionId: $selectionId ?? $this->selection($source['selection'] ?? null, $context),
            tri: $tri,
            page: $this->page($source['page'] ?? null),
            taille: $this->size($source['taille'] ?? null),
            contexte: $context,
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function input(array $query): array
    {
        $known = [
            'texte', 'categorie', 'format', 'langue', 'niveau', 'prix_min', 'prix_max', 'devise',
            'modalite', 'gratuite', 'etat', 'disponibilite', 'badge', 'africain', 'auteur',
            'editeur', 'selection', 'tri', 'page', 'taille', 'descendance',
        ];
        $input = [];
        foreach ($known as $key) {
            if (array_key_exists($key, $query)) {
                $input[$key] = $query[$key];
            }
        }

        $this->modality($input['modalite'] ?? null);

        return $input;
    }

    /**
     * @return array<string, mixed>
     */
    private function document(mixed $parameters): array
    {
        if ($parameters === null || $parameters === '') {
            return [];
        }
        if (is_string($parameters)) {
            $decoded = json_decode($parameters, true);
            if (! is_array($decoded)) {
                throw CatalogueConsultationException::invalid('selection', 'Cette sélection ne peut pas être consultée.');
            }
            $parameters = $decoded;
        }
        if (! is_array($parameters) || array_is_list($parameters)) {
            throw CatalogueConsultationException::invalid('selection', 'Cette sélection ne peut pas être consultée.');
        }

        $aliases = [
            'availability' => 'disponibilite',
            'text' => 'texte',
            'currency' => 'devise',
            'sort' => 'tri',
            'categories' => 'categorie',
        ];
        $allowed = [
            'texte', 'categorie', 'format', 'langue', 'niveau', 'prix_min', 'prix_max', 'devise',
            'modalite', 'gratuite', 'etat', 'disponibilite', 'badge', 'africain', 'auteur',
            'editeur', 'tri', 'page', 'taille', 'descendance',
            'availability', 'text', 'currency', 'sort', 'categories',
        ];
        $normalized = [];
        foreach ($parameters as $key => $value) {
            if (! is_string($key) || ! in_array($key, $allowed, true)) {
                throw CatalogueConsultationException::invalid('selection', 'Cette sélection ne peut pas être consultée.');
            }
            $normalized[$aliases[$key] ?? $key] = $value;
        }

        return $this->input($normalized);
    }

    private function activeSelection(string $code): stdClass
    {
        if (! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $code)) {
            throw CatalogueConsultationException::absent();
        }

        $selection = DB::table('selection_editoriale')->where('code', $code)->first();
        if ($selection === null || (int) $selection->actif !== 1) {
            throw CatalogueConsultationException::absent();
        }

        return $selection;
    }

    /**
     * @return list<string>
     */
    private function categories(mixed $value, bool $descendants): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }
        $values = is_array($value) ? $value : [$value];
        $ids = [];
        foreach ($values as $item) {
            if (! is_scalar($item)) {
                throw CatalogueConsultationException::invalid('categorie', 'Cette catégorie n’est pas recevable.');
            }
            foreach ($this->taxonomy->categoryIds((string) $item, $descendants) as $id) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    private function text(mixed $value): ?string
    {
        if (! $this->present($value)) {
            return null;
        }
        if (! is_string($value)) {
            throw CatalogueConsultationException::invalid('texte', 'Le texte recherché n’est pas recevable.');
        }
        $text = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        if ($text === '') {
            return null;
        }
        if (mb_strlen($text) > 200 || str_contains($text, "\0")) {
            throw CatalogueConsultationException::invalid('texte', 'Le texte recherché est trop long.');
        }

        return $text;
    }

    private function format(mixed $value): ?string
    {
        if (! $this->present($value)) {
            return null;
        }
        if (! is_string($value) || ! preg_match('/^[a-z0-9_-]{1,32}$/', $value)) {
            throw CatalogueConsultationException::invalid('format', 'Ce format n’est pas consultable.');
        }
        $exists = DB::table('format_technique')->where('code', $value)->where('actif', 1)->exists();
        if (! $exists) {
            throw CatalogueConsultationException::invalid('format', 'Ce format n’est pas consultable.');
        }

        return $value;
    }

    private function money(mixed $value, string $field, mixed $minimum = null): ?string
    {
        if (! $this->present($value)) {
            return null;
        }
        if (! is_scalar($value) || ! preg_match('/^\d{1,16}(\.\d{1,4})?$/', trim((string) $value))) {
            throw CatalogueConsultationException::invalid($field, 'Ce montant n’est pas recevable.');
        }
        $amount = $this->padMoney(trim((string) $value));
        if ($field === 'prix_max' && $this->present($minimum)) {
            $floor = $this->money($minimum, 'prix_min');
            if ($floor !== null && strcmp($amount, $floor) < 0) {
                throw CatalogueConsultationException::invalid('prix_max', 'Le prix maximum est inférieur au prix minimum.');
            }
        }

        return $amount;
    }

    private function currency(mixed $value): string
    {
        if (! $this->present($value)) {
            return 'USD';
        }
        if (! is_string($value)) {
            throw CatalogueConsultationException::invalid('devise', 'Cette devise n’est pas acceptée.');
        }
        $currency = strtoupper(trim($value));
        if (! in_array($currency, ['USD', 'CDF'], true)) {
            throw CatalogueConsultationException::invalid('devise', 'Cette devise n’est pas acceptée.');
        }

        return $currency;
    }

    private function availability(mixed $value): ?string
    {
        if (! $this->present($value)) {
            return null;
        }
        if ($value === 'free') {
            throw CatalogueConsultationException::invalid('disponibilite', 'La disponibilité free n’existe pas.');
        }

        return $this->enum($value, 'disponibilite', ['available', 'soon']);
    }

    private function modality(mixed $value): void
    {
        if (! $this->present($value)) {
            return;
        }
        if (! is_string($value)) {
            throw CatalogueConsultationException::invalid('modalite', 'Cette modalité n’est pas consultable.');
        }
        if ($value === 'achat') {
            return;
        }
        if ($value === 'location' || $value === 'editeur_label') {
            throw CatalogueConsultationException::invalid('modalite', 'Cette modalité n’est pas consultable.');
        }

        throw CatalogueConsultationException::invalid('modalite', 'Cette modalité n’est pas consultable.');
    }

    /**
     * @param  list<string>  $allowed
     */
    private function enum(mixed $value, string $field, array $allowed): ?string
    {
        if (! $this->present($value)) {
            return null;
        }
        if (! is_string($value) || ! in_array($value, $allowed, true)) {
            throw CatalogueConsultationException::invalid($field, 'Cette valeur n’est pas recevable.');
        }

        return $value;
    }

    private function badge(mixed $value): ?string
    {
        if (! $this->present($value)) {
            return null;
        }
        if (! is_string($value)) {
            throw CatalogueConsultationException::invalid('badge', 'Ce badge n’est pas recevable.');
        }
        $badge = trim($value);
        if ($badge === '' || mb_strlen($badge) > 80) {
            throw CatalogueConsultationException::invalid('badge', 'Ce badge n’est pas recevable.');
        }

        return $badge;
    }

    private function author(mixed $value): ?string
    {
        if (! $this->present($value)) {
            return null;
        }
        if (! is_string($value)) {
            throw CatalogueConsultationException::invalid('auteur', 'Cet auteur n’est pas consultable.');
        }
        $query = DB::table('auteur');
        if (IdentityUlid::isValid($value)) {
            $query->where('auteur.auteur_id', $value);
        } else {
            $query->where('auteur.slug', $value);
        }
        $this->visibility->constrainVisibleAuthor($query);
        $author = $query->first();
        if ($author === null) {
            throw CatalogueConsultationException::invalid('auteur', 'Cet auteur n’est pas consultable.');
        }

        return $author->auteur_id;
    }

    private function publisher(mixed $value): ?string
    {
        if (! $this->present($value)) {
            return null;
        }
        if (! is_string($value)) {
            throw CatalogueConsultationException::invalid('editeur', 'Cet éditeur n’est pas consultable.');
        }
        $query = DB::table('editeur')->where('actif', 1);
        if (IdentityUlid::isValid($value)) {
            $query->where('editeur_id', $value);
        } else {
            $query->where('slug', $value);
        }
        $publisher = $query->first();
        if ($publisher === null) {
            throw CatalogueConsultationException::invalid('editeur', 'Cet éditeur n’est pas consultable.');
        }

        return $publisher->editeur_id;
    }

    private function selection(mixed $value, string $context): ?string
    {
        if (! $this->present($value) || $context !== CatalogueCriteria::GENERAL) {
            return null;
        }
        if (! is_string($value)) {
            throw CatalogueConsultationException::absent();
        }
        $selection = $this->activeSelection($value);
        if ($selection->code === 'a_paraitre' || $selection->regle_ou_manuelle === 'filtre') {
            return null;
        }

        return $selection->selection_id;
    }

    private function sort(mixed $value, ?string $selectionId, bool $dedicatedManual): string
    {
        if (! $this->present($value)) {
            return $selectionId !== null ? 'selection' : 'recent';
        }
        if (! is_string($value)) {
            throw CatalogueConsultationException::invalid('tri', 'Ce tri n’est pas disponible.');
        }
        if ($value === 'popular') {
            throw CatalogueConsultationException::invalid('tri', 'Le tri par popularité n’est pas disponible.');
        }
        if (! in_array($value, ['recent', 'oldest', 'price_asc', 'price_desc', 'selection'], true)) {
            throw CatalogueConsultationException::invalid('tri', 'Ce tri n’est pas disponible.');
        }
        if ($value === 'selection' && $selectionId === null) {
            throw CatalogueConsultationException::invalid('tri', 'Le tri de sélection exige une sélection manuelle.');
        }
        if ($dedicatedManual && $selectionId !== null) {
            return 'selection';
        }

        return $value;
    }

    private function page(mixed $value): int
    {
        $page = $this->integer($value, 'page', 1);
        if ($page > 10000) {
            throw CatalogueConsultationException::invalid('page', 'Cette page n’est pas recevable.');
        }

        return $page;
    }

    private function size(mixed $value): int
    {
        $size = $this->integer($value, 'taille', 20);
        if ($size > 50) {
            throw CatalogueConsultationException::invalid('taille', 'Cette taille de page n’est pas recevable.');
        }

        return $size;
    }

    private function integer(mixed $value, string $field, int $default): int
    {
        if (! $this->present($value)) {
            return $default;
        }
        if (is_int($value) && $value >= 1) {
            return $value;
        }
        if (is_string($value) && preg_match('/^[1-9]\d*$/', $value)) {
            return (int) $value;
        }

        throw CatalogueConsultationException::invalid($field, 'Cette valeur n’est pas recevable.');
    }

    private function flag(mixed $value, string $field): ?bool
    {
        if (! $this->present($value)) {
            return null;
        }
        if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
            return true;
        }
        if ($value === false || $value === 0 || $value === '0' || $value === 'false') {
            return false;
        }

        throw CatalogueConsultationException::invalid($field, 'Cette valeur n’est pas recevable.');
    }

    private function optionalResolved(mixed $value, callable $resolve): ?string
    {
        if (! $this->present($value)) {
            return null;
        }
        if (! is_string($value)) {
            throw CatalogueConsultationException::invalid('critere', 'Cette valeur n’est pas recevable.');
        }

        return $resolve($value);
    }

    private function withAvailability(CatalogueCriteria $criteria, string $availability, string $context): CatalogueCriteria
    {
        return new CatalogueCriteria(
            texte: $criteria->texte,
            categorieIds: $criteria->categorieIds,
            formatCode: $criteria->formatCode,
            langueId: $criteria->langueId,
            niveauId: $criteria->niveauId,
            prixMin: $criteria->prixMin,
            prixMax: $criteria->prixMax,
            devise: $criteria->devise,
            gratuite: $criteria->gratuite,
            etat: $criteria->etat,
            disponibilite: $availability,
            badge: $criteria->badge,
            africain: $criteria->africain,
            auteurId: $criteria->auteurId,
            editeurId: $criteria->editeurId,
            selectionId: null,
            tri: $criteria->tri,
            page: $criteria->page,
            taille: $criteria->taille,
            contexte: $context,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function withoutEmpty(array $input): array
    {
        return array_filter($input, fn (mixed $value): bool => $this->present($value));
    }

    private function present(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    private function padMoney(string $amount): string
    {
        if (! str_contains($amount, '.')) {
            return $amount.'.0000';
        }
        [$whole, $fraction] = explode('.', $amount, 2);

        return $whole.'.'.str_pad(substr($fraction, 0, 4), 4, '0');
    }
}
