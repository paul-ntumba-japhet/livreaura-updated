<?php

namespace App\Modules\Catalogue\Presentation\Console\Commands;

use App\Modules\Catalogue\Infrastructure\Recipe\CatalogueRecipeImporter;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class ImportCatalogueRecipeCommand extends Command
{
    protected $signature = 'catalogue:import-recette
                            {--vendeur= : Identifiant du vendeur actif auquel rattacher VEN-001}
                            {--classeur= : Chemin du classeur de recette}
                            {--medias= : Dossier des fichiers de recette}';

    protected $description = 'Importe le classeur de recette A2 dans les tables actuelles, sans publication A5.';

    public function handle(): int
    {
        if ($this->laravel->environment('production')) {
            $this->error('Cet import de recette ne s’exécute pas en production.');

            return self::FAILURE;
        }

        $root = dirname(base_path());
        $workbook = $this->option('classeur') ?: $root.DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'chaineA2'.DIRECTORY_SEPARATOR.'Livreaura_A2_Catalogue_Test.xlsx';
        $medias = $this->option('medias') ?: $root.DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'chaineA2'.DIRECTORY_SEPARATOR.'medias';
        $vendor = $this->option('vendeur');
        $vendor = is_string($vendor) && $vendor !== '' ? $vendor : null;

        if (! is_file($workbook) || ! is_dir($medias)) {
            $this->error('Le classeur ou le dossier des médias est introuvable.');

            return self::FAILURE;
        }

        try {
            $result = (new CatalogueRecipeImporter(
                $workbook,
                $medias,
                storage_path('app'.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalogue-recette'),
                $vendor,
            ))->import();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('L’import a été annulé : '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('VEN-001 est rattaché au vendeur '.$result['vendeur_id'].'.');
        foreach ($result['comptes'] as $name => $count) {
            $this->line($name.' : '.$count);
        }
        $this->line('Les fichiers sont copiés hors de la racine web. Les médias restent inactifs, comme dans le classeur. Le stock A3 n’est pas écrit.');

        return self::SUCCESS;
    }
}
