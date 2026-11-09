<?php

namespace App\Modules\Catalogue\Presentation\Console\Commands;

use App\Modules\Catalogue\Application\Projection\RecordCatalogueWork;
use App\Modules\Catalogue\Application\Projection\SynchronizeCatalogueProjection;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageReception;
use Illuminate\Console\Command;

class DispatchCatalogueWorkCommand extends Command
{
    protected $signature = 'a2:dispatch-catalogue {--limite=50}';

    protected $description = 'Reprend les synchronisations de catalogue déjà engagées et non encore exécutées';

    public function handle(SynchronizeCatalogueProjection $projection): int
    {
        $limit = max(1, min((int) $this->option('limite'), 200));
        $ids = MessageReception::query()
            ->where('producteur', 'A2')
            ->where('consommateur', RecordCatalogueWork::CONSUMER)
            ->whereNull('traite_a')
            ->orderBy('recu_a')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');
        $done = 0;

        foreach ($ids as $id) {
            $projection->forReception((string) $id);
            $done++;
        }

        $this->info($done.' réception(s) relue(s).');

        return self::SUCCESS;
    }
}
