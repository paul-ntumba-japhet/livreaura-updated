<?php

namespace App\Modules\Catalogue\Infrastructure\Recipe;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Charge le classeur de recette A2 dans les tables existantes.
 * Ce n'est pas une publication A5 ni une action du catalogue.
 */
final class CatalogueRecipeImporter
{
    /** @var array<string, string> */
    private const LANGUAGE_CODES = [
        'LAN-001' => 'fr',
        'LAN-002' => 'en',
        'LAN-003' => 'ln',
        'LAN-004' => 'sw',
        'LAN-005' => 'lu',
        'LAN-006' => 'kg',
        'LAN-007' => 'pt',
        'LAN-008' => 'ar',
        'LAN-009' => 'es',
        'LAN-010' => 'de',
    ];

    private readonly string $vendorId;

    /** @var array<string, string> */
    private array $ids = [];

    /** @var array<string, int> */
    private array $counts = [];

    public function __construct(
        private readonly string $workbookPath,
        private readonly string $mediaDirectory,
        private readonly string $mediaRoot,
        ?string $vendorId,
    ) {
        $this->vendorId = $this->vendor($vendorId);
    }

    /**
     * @return array{vendeur_id: string, comptes: array<string, int>}
     */
    public function import(): array
    {
        $sheets = (new CatalogueRecipeWorkbook($this->workbookPath))->sheets();
        $this->requireSheets($sheets);
        $languages = $this->references($sheets['References externes'], 'langue');
        $categories = $this->references($sheets['References externes'], 'categorie');
        $levels = $this->references($sheets['References externes'], 'niveau');
        $authors = $this->references($sheets['References externes'], 'auteur');
        $publishers = $this->references($sheets['References externes'], 'editeur');
        $collections = $this->references($sheets['References externes'], 'collection');
        $works = $sheets['Oeuvres'];
        $contributions = $sheets['Oeuvres auteurs'];
        $workCategories = $sheets['Oeuvres categories'];
        $workLanguages = $sheets['Oeuvres langues'];
        $editions = $sheets['Editions'];
        $offers = $sheets['Offres'];
        $media = $sheets['Medias a preparer'];
        $selections = $sheets['Selections'];
        $selectionWorks = $sheets['Selection oeuvres'];

        $this->assertOffersUseMappedVendor($offers);
        $this->assertFiles($media);
        $collectionPublishers = $this->collectionPublishers($works, $editions);

        DB::transaction(function () use (
            $languages,
            $categories,
            $levels,
            $authors,
            $publishers,
            $collections,
            $collectionPublishers,
            $works,
            $contributions,
            $workCategories,
            $workLanguages,
            $editions,
            $offers,
            $media,
            $selections,
            $selectionWorks,
        ): void {
            foreach ($languages as $reference => $label) {
                $this->language($reference, $label);
            }
            foreach ($categories as $reference => $label) {
                $this->category($reference, $label);
            }
            foreach ($levels as $reference => $label) {
                $this->level($reference, $label);
            }
            foreach ($authors as $reference => $label) {
                $this->author($reference, $label);
            }
            foreach ($publishers as $reference => $label) {
                $this->publisher($reference, $label);
            }
            foreach ($collections as $reference => $label) {
                $this->collection($reference, $label, $collectionPublishers[$reference] ?? null);
            }
            foreach ($works as $row) {
                $this->work($row);
            }
            foreach ($contributions as $row) {
                $this->contribution($row);
            }
            foreach ($workCategories as $row) {
                $this->workCategory($row);
            }
            foreach ($workLanguages as $row) {
                $this->workLanguage($row);
            }
            foreach ($editions as $row) {
                $this->edition($row);
            }
            foreach ($offers as $row) {
                $this->offer($row);
            }
            foreach ($media as $row) {
                $this->medium($row);
            }
            foreach ($selections as $row) {
                $this->selection($row);
            }
            foreach ($selectionWorks as $row) {
                $this->selectionWork($row);
            }
        });

        $this->copyMedia($media);

        return [
            'vendeur_id' => $this->vendorId,
            'comptes' => $this->counts,
        ];
    }

    private function vendor(?string $requested): string
    {
        if ($requested !== null && $requested !== '') {
            $vendor = DB::table('vendeurs')->where('id', $requested)->first();
            if ($vendor === null || (int) $vendor->actif !== 1) {
                throw new RuntimeException('Le vendeur indiqué est absent ou inactif.');
            }

            return $vendor->id;
        }

        $vendors = DB::table('vendeurs')->where('actif', 1)->pluck('id');
        if ($vendors->count() !== 1) {
            throw new RuntimeException('Indiquez --vendeur : VEN-001 ne peut pas être choisi parmi plusieurs vendeurs actifs.');
        }

        return (string) $vendors->first();
    }

    /**
     * @param  array<string, list<array<string, string>>>  $sheets
     */
    private function requireSheets(array $sheets): void
    {
        foreach ([
            'Oeuvres', 'Oeuvres auteurs', 'Oeuvres categories', 'Oeuvres langues',
            'Editions', 'Offres', 'Medias a preparer', 'Selections', 'Selection oeuvres',
            'References externes',
        ] as $name) {
            if (! isset($sheets[$name])) {
                throw new RuntimeException('La feuille '.$name.' est absente du classeur.');
            }
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return array<string, string>
     */
    private function references(array $rows, string $type): array
    {
        $references = [];
        foreach ($rows as $row) {
            if (($row['type'] ?? '') !== $type) {
                continue;
            }
            $references[$row['reference']] = $row['libelle'];
        }
        if ($references === []) {
            throw new RuntimeException('Le référentiel '.$type.' est vide.');
        }

        return $references;
    }

    /**
     * @param  list<array<string, string>>  $offers
     */
    private function assertOffersUseMappedVendor(array $offers): void
    {
        foreach ($offers as $offer) {
            if (($offer['vendeur'] ?? '') !== 'VEN-001') {
                throw new RuntimeException('Une offre cite un vendeur autre que VEN-001.');
            }
        }
    }

    /**
     * @param  list<array<string, string>>  $media
     */
    private function assertFiles(array $media): void
    {
        foreach ($media as $row) {
            $path = $this->sourceMedia($row['chemin_prevu']);
            if (! is_file($path) || filesize($path) === 0) {
                throw new RuntimeException('Le fichier de recette '.$row['chemin_prevu'].' est absent.');
            }
        }
    }

    /**
     * @param  list<array<string, string>>  $works
     * @param  list<array<string, string>>  $editions
     * @return array<string, string|null>
     */
    private function collectionPublishers(array $works, array $editions): array
    {
        $workCollection = [];
        foreach ($works as $work) {
            if (($work['collection'] ?? '') !== '') {
                $workCollection[$work['reference']] = $work['collection'];
            }
        }

        $publishers = [];
        foreach ($editions as $edition) {
            $collection = $workCollection[$edition['oeuvre']] ?? null;
            if ($collection === null) {
                continue;
            }
            $publishers[$collection][$edition['editeur']] = true;
        }

        $resolved = [];
        foreach ($publishers as $collection => $codes) {
            $codes = array_keys($codes);
            $resolved[$collection] = count($codes) === 1 ? $codes[0] : null;
        }

        return $resolved;
    }

    private function language(string $reference, string $label): void
    {
        $code = self::LANGUAGE_CODES[$reference] ?? null;
        if ($code === null) {
            throw new RuntimeException('Le code de langue '.$reference.' n’est pas fixé.');
        }
        $this->record('langue', 'langue_id', 'code', $code, $reference, [
            'nom' => $label,
            'actif' => 1,
        ]);
    }

    private function category(string $reference, string $label): void
    {
        $this->record('categorie', 'categorie_id', 'slug', $this->slug($label), $reference, [
            'parent_id' => null,
            'nom' => $label,
            'sort_order' => $this->sequence($reference),
            'actif' => 1,
        ], 'nom');
    }

    private function level(string $reference, string $label): void
    {
        $this->record('niveau_educatif', 'niveau_educatif_id', 'slug', $this->slug($label), $reference, [
            'parent_id' => null,
            'nom' => $label,
            'sort_order' => $this->sequence($reference),
            'actif' => 1,
        ], 'nom');
    }

    private function author(string $reference, string $label): void
    {
        $this->record('auteur', 'auteur_id', 'slug', $this->slug($label), $reference, [
            'compte_id' => null,
            'nom_affiche' => $label,
            'bio' => null,
            'pays' => null,
            'role_affiche' => null,
            'verified' => 0,
            'mis_en_avant' => 0,
            'actif' => 1,
            'statut_publication' => 'published',
        ], 'nom_affiche');
    }

    private function publisher(string $reference, string $label): void
    {
        $this->record('editeur', 'editeur_id', 'slug', $this->slug($label), $reference, [
            'nom' => $label,
            'type' => 'publisher',
            'presentation' => null,
            'pays' => null,
            'is_partner' => 0,
            'is_academic' => 0,
            'is_youth' => 0,
            'is_african' => 0,
            'is_indie' => 0,
            'mis_en_avant' => 0,
            'actif' => 1,
        ], 'nom');
    }

    private function collection(string $reference, string $label, ?string $publisherReference): void
    {
        $this->record('collection', 'collection_id', 'slug', $this->slug($label), $reference, [
            'editeur_id' => $publisherReference === null ? null : $this->id($publisherReference),
            'nom' => $label,
            'kind' => $reference === 'COL-003' ? 'bundle' : 'collection',
            'actif' => 1,
        ], 'nom');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function work(array $row): void
    {
        $level = $this->nullable($row['niveau'] ?? '');
        $collection = $this->nullable($row['collection'] ?? '');
        $this->record('oeuvre', 'oeuvre_id', 'slug', $row['slug'], $row['reference'], [
            'titre' => $row['titre'],
            'sous_titre' => $this->nullable($row['sous_titre'] ?? ''),
            'resume' => $this->nullable($row['resume'] ?? ''),
            'pages' => $this->nullable($row['pages'] ?? ''),
            'langue_principale_id' => $this->id($row['langue_principale']),
            'statut' => $this->oneOf($row['statut'], ['published'], 'statut'),
            'availability' => $this->oneOf($row['availability'], ['available', 'soon'], 'availability'),
            'badge' => null,
            'africain' => $this->flag($row['africain']),
            'mis_en_avant' => $this->flag($row['mis_en_avant']),
            'publie_a' => $this->instant($row['publie_a_utc']),
            'note_moyenne' => null,
            'avis_count' => 0,
            'retire_a' => null,
            'niveau_educatif_id' => $level === null ? null : $this->id($level),
            'collection_id' => $collection === null ? null : $this->id($collection),
        ], 'titre');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function contribution(array $row): void
    {
        DB::table('oeuvre_auteur')->updateOrInsert(
            ['oeuvre_id' => $this->id($row['oeuvre']), 'auteur_id' => $this->id($row['auteur'])],
            ['ordre_affichage' => (int) $row['ordre_affichage'], 'role' => $row['role']],
        );
        $this->count('contributions');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function workCategory(array $row): void
    {
        DB::table('oeuvre_categorie')->insertOrIgnore([
            'oeuvre_id' => $this->id($row['oeuvre']),
            'categorie_id' => $this->id($row['categorie']),
        ]);
        $this->count('classements');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function workLanguage(array $row): void
    {
        DB::table('oeuvre_langue')->insertOrIgnore([
            'oeuvre_id' => $this->id($row['oeuvre']),
            'langue_id' => $this->id($row['langue']),
        ]);
        $this->count('langues_oeuvre');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function edition(array $row): void
    {
        $workId = $this->id($row['oeuvre']);
        $title = $row['titre_edition'];
        $existing = DB::table('edition')
            ->where('oeuvre_id', $workId)
            ->where('titre_edition', $title)
            ->first();
        if ($existing !== null) {
            $this->ids[$row['reference']] = $existing->edition_id;
            $this->count('editions_reprises');

            return;
        }

        $id = IdentityUlid::make();
        DB::table('edition')->insert([
            'edition_id' => $id,
            'oeuvre_id' => $workId,
            'editeur_id' => $this->id($row['editeur']),
            'isbn' => null,
            'titre_edition' => $title,
            'editeur_legal_libelle' => $row['editeur_legal_libelle'],
            'role_ae' => $this->oneOf($row['role_ae'], ['distributeur', 'prestataire', 'editeur_label'], 'role_ae'),
            'responsable_depot' => null,
            'territoire' => null,
            'date_edition' => $row['date_edition'],
            'statut_depot' => null,
            'statut' => $this->oneOf($row['statut'], ['active', 'archived'], 'statut'),
            'consultable' => $this->flag($row['consultable']),
        ]);
        $this->ids[$row['reference']] = $id;
        $this->count('editions');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function offer(array $row): void
    {
        $format = DB::table('format_technique')->where('code', $row['format'])->where('actif', 1)->first();
        if ($format === null) {
            throw new RuntimeException('Le format '.$row['format'].' est absent.');
        }
        $editionId = $this->id($row['edition']);
        $condition = $this->oneOf($row['condition'], ['none', 'new', 'used'], 'condition');
        $modality = $this->oneOf($row['modalite'], ['achat'], 'modalite');
        $currency = $this->oneOf($row['devise'], ['USD', 'CDF'], 'devise');
        $existing = DB::table('offre')
            ->where('edition_id', $editionId)
            ->where('format_id', $format->format_id)
            ->where('condition', $condition)
            ->where('modalite_commerciale', $modality)
            ->where('devise', $currency)
            ->first();
        if ($existing !== null) {
            if ($existing->vendeur_id !== $this->vendorId) {
                throw new RuntimeException('L’offre '.$row['reference'].' existe déjà pour un autre vendeur.');
            }
            $this->ids[$row['reference']] = $existing->offre_id;
            $this->count('offres_reprises');

            return;
        }

        $id = IdentityUlid::make();
        DB::table('offre')->insert([
            'offre_id' => $id,
            'edition_id' => $editionId,
            'format_id' => $format->format_id,
            'vendeur_id' => $this->vendorId,
            'modalite_commerciale' => $modality,
            'prix' => $row['prix'],
            'devise' => $currency,
            'condition' => $condition,
            'sku' => null,
            'actif' => $this->flag($row['actif']),
            'stock' => null,
            'retire_a' => null,
        ]);
        $this->ids[$row['reference']] = $id;
        $this->count('offres');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function medium(array $row): void
    {
        $work = $this->nullable($row['oeuvre'] ?? '');
        $edition = $this->nullable($row['edition'] ?? '');
        if (($work === null) === ($edition === null)) {
            throw new RuntimeException('Le média '.$row['reference'].' doit viser une œuvre ou une édition.');
        }
        $type = $this->oneOf($row['type'], ['couverture', 'galerie', 'apercu'], 'type');
        $volume = $this->nullable($row['volume_apercu'] ?? '');
        $unit = $this->nullable($row['unite_volume'] ?? '');
        if ($type === 'apercu' && ($volume === null || $unit === null)) {
            throw new RuntimeException('L’aperçu '.$row['reference'].' n’a pas de volume.');
        }
        if ($type !== 'apercu' && ($volume !== null || $unit !== null)) {
            throw new RuntimeException('La couverture '.$row['reference'].' ne doit pas porter de volume.');
        }

        $reference = 'catalogue-recette/'.$row['chemin_prevu'];
        $existing = DB::table('media_catalogue')->where('reference_publique', $reference)->first();
        if ($existing !== null) {
            $this->ids[$row['reference']] = $existing->media_catalogue_id;
            $this->count('medias_repris');

            return;
        }

        $id = IdentityUlid::make();
        DB::table('media_catalogue')->insert([
            'media_catalogue_id' => $id,
            'oeuvre_id' => $work === null ? null : $this->id($work),
            'edition_id' => $edition === null ? null : $this->id($edition),
            'type' => $type,
            'reference_publique' => $reference,
            'texte_alternatif' => $row['texte_alternatif'],
            'ordre' => (int) $row['ordre'],
            'actif' => $this->flag($row['actif']),
            'retire_a' => null,
            'volume_apercu' => $volume,
            'unite_volume' => $unit,
        ]);
        $this->ids[$row['reference']] = $id;
        $this->count('medias');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function selection(array $row): void
    {
        $mode = $this->oneOf($row['mode'], ['filtre', 'manuelle'], 'mode');
        $parameters = $this->nullable($row['parametres_json'] ?? '');
        if ($parameters !== null && json_decode($parameters, true) === null) {
            throw new RuntimeException('Les paramètres de '.$row['code'].' ne sont pas un document JSON.');
        }
        if ($mode === 'manuelle') {
            $parameters = null;
        }
        $this->record('selection_editoriale', 'selection_id', 'code', $row['code'], 'selection:'.$row['code'], [
            'libelle' => $row['libelle'],
            'regle_ou_manuelle' => $mode,
            'parametres' => $parameters,
            'actif' => $this->flag($row['actif']),
            'sort_order' => (int) $row['ordre'],
        ], 'libelle');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function selectionWork(array $row): void
    {
        DB::table('selection_oeuvre')->updateOrInsert(
            [
                'selection_id' => $this->id('selection:'.$row['selection']),
                'oeuvre_id' => $this->id($row['oeuvre']),
            ],
            [
                'sort_order' => (int) $row['ordre'],
                'ajoute_a' => $this->instant($row['ajoute_a_utc']),
            ],
        );
        $this->count('selection_oeuvres');
    }

    /**
     * @param  list<array<string, string>>  $media
     */
    private function copyMedia(array $media): void
    {
        foreach ($media as $row) {
            $target = $this->mediaRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $row['chemin_prevu']);
            $directory = dirname($target);
            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException('Le dossier des dérivés ne peut pas être créé.');
            }
            if (! copy($this->sourceMedia($row['chemin_prevu']), $target)) {
                throw new RuntimeException('Le fichier '.$row['chemin_prevu'].' n’a pas été copié.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function record(
        string $table,
        string $idColumn,
        string $keyColumn,
        string $key,
        string $reference,
        array $values,
        ?string $guard = null,
    ): void {
        $existing = DB::table($table)->where($keyColumn, $key)->first();
        if ($existing !== null) {
            if ($guard !== null && (string) $existing->{$guard} !== (string) $values[$guard]) {
                throw new RuntimeException('La clé '.$key.' existe déjà avec une autre valeur.');
            }
            $this->ids[$reference] = $existing->{$idColumn};
            $this->count($table.'_repris');

            return;
        }

        $id = IdentityUlid::make();
        DB::table($table)->insert([$idColumn => $id, $keyColumn => $key] + $values);
        $this->ids[$reference] = $id;
        $this->count($table);
    }

    private function id(string $reference): string
    {
        $id = $this->ids[$reference] ?? null;
        if ($id === null) {
            throw new RuntimeException('La référence '.$reference.' n’est pas résolue.');
        }

        return $id;
    }

    private function sourceMedia(string $relative): string
    {
        return $this->mediaDirectory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private function sequence(string $reference): int
    {
        if (! preg_match('/(\d+)$/', $reference, $match)) {
            throw new RuntimeException('La référence '.$reference.' n’a pas d’ordre.');
        }

        return (int) $match[1];
    }

    /**
     * @param  list<string>  $allowed
     */
    private function oneOf(string $value, array $allowed, string $field): string
    {
        if (! in_array($value, $allowed, true)) {
            throw new RuntimeException('La valeur '.$field.' « '.$value.' » n’est pas recevable.');
        }

        return $value;
    }

    private function flag(string $value): int
    {
        if ($value !== '0' && $value !== '1') {
            throw new RuntimeException('Un indicateur du classeur n’est pas 0 ou 1.');
        }

        return (int) $value;
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function instant(string $value): string
    {
        $instant = new DateTimeImmutable($value);
        $instant = $instant->setTimezone(new DateTimeZone('UTC'));

        return $instant->format('Y-m-d H:i:s.u');
    }

    private function slug(string $label): string
    {
        $label = preg_replace('/^\[TEST\]\s*/u', '', $label) ?? $label;
        $label = strtr($label, [
            'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Î' => 'I', 'Ï' => 'I', 'Ô' => 'O', 'Ö' => 'O', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C', 'Œ' => 'OE', 'Æ' => 'AE',
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'œ' => 'oe', 'æ' => 'ae',
        ]);
        $label = strtolower($label);
        $label = preg_replace('/[^a-z0-9]+/', '-', $label) ?? $label;

        return trim($label, '-');
    }

    private function count(string $name): void
    {
        $this->counts[$name] = ($this->counts[$name] ?? 0) + 1;
    }
}
