<?php

namespace App\Modules\Catalogue\Presentation\Console\Commands;

use App\Modules\Catalogue\Application\Actions\Reviews\RecalculateReviewAggregates;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateCatalogueAggregatesCommand extends Command
{
    protected $signature = 'a2:recalculate-aggregates {oeuvre?} {--limite=50}';

    protected $description = 'Recalcule la note et le nombre d’avis publiés d’une œuvre ou d’un lot';

    public function handle(RecalculateReviewAggregates $aggregates): int
    {
        $workId = $this->argument('oeuvre');
        $ids = is_string($workId) && $workId !== ''
            ? [strtoupper($workId)]
            : DB::table('avis')
                ->select('oeuvre_id')
                ->distinct()
                ->orderBy('oeuvre_id')
                ->limit(max(1, min((int) $this->option('limite'), 200)))
                ->pluck('oeuvre_id')
                ->all();
        $done = 0;

        foreach ($ids as $id) {
            if (! is_string($id) || ! IdentityUlid::isValid($id)) {
                continue;
            }

            $aggregates->forWork($id);
            $done++;
        }

        $this->info($done.' œuvre(s) recalculée(s).');

        return self::SUCCESS;
    }
}
