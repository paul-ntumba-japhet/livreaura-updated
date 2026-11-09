<?php

namespace App\Modules\Catalogue\Presentation\Console\Commands;

use App\Modules\Catalogue\Application\Projection\ApplyCatalogueRestriction;
use App\Modules\Catalogue\Application\Projection\RecordCatalogueWork;
use App\Modules\Catalogue\Application\Projection\SynchronizeCatalogueProjection;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageReception;
use Illuminate\Console\Command;

class ResumeCatalogueCommand extends Command
{
    protected $signature = 'a2:resume-catalogue {--limite=50}';

    protected $description = 'Reprend les réceptions de catalogue non traitées';

    public function handle(SynchronizeCatalogueProjection $projection, ApplyCatalogueRestriction $restrictions): int
    {
        $limit = max(1, min((int) $this->option('limite'), 200));
        $rows = MessageReception::query()
            ->where('consommateur', RecordCatalogueWork::CONSUMER)
            ->whereNull('traite_a')
            ->orderBy('recu_a')
            ->orderBy('id')
            ->limit($limit)
            ->get();
        $done = 0;

        foreach ($rows as $row) {
            try {
                if ($row->producteur === 'A2') {
                    $projection->forReception($row->id);
                } elseif (in_array($row->producteur, ['A5', 'A6'], true)) {
                    $restrictions->forReception($row->id);
                } else {
                    continue;
                }
            } catch (CatalogueConsultationException) {
                continue;
            }

            $done++;
        }

        $this->info($done.' réception(s) reprises.');

        return self::SUCCESS;
    }
}
