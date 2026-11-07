<?php

namespace App\Modules\IdentityAccess\Presentation\Console\Commands;

use App\Modules\IdentityAccess\Application\Actions\Messaging\ResumeUntreatedReceptions;
use Illuminate\Console\Command;

class ResumeInboxCommand extends Command
{
    protected $signature = 'a1:resume-inbox {--limit=50 : Nombre maximal de receptions relues}';

    protected $description = 'Reprend les receptions non traitees dont la correlation est maintenant disponible.';

    public function handle(ResumeUntreatedReceptions $resume): int
    {
        $processed = $resume((int) $this->option('limit'));

        $this->info(count($processed).' reception(s) reprise(s).');

        return self::SUCCESS;
    }
}
