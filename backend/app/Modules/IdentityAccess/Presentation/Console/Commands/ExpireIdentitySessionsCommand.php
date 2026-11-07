<?php

namespace App\Modules\IdentityAccess\Presentation\Console\Commands;

use App\Modules\IdentityAccess\Application\Actions\Sessions\ExpireAuthenticationSessions;
use Illuminate\Console\Command;

class ExpireIdentitySessionsCommand extends Command
{
    protected $signature = 'a1:expire-sessions {--limit=50 : Nombre maximal de sessions relues}';

    protected $description = 'Passe les sessions actives echeues a expired, sans restaurer un acces.';

    public function handle(ExpireAuthenticationSessions $expire): int
    {
        $changed = $expire((int) $this->option('limit'));

        $this->info(count($changed).' session(s) expiree(s).');

        return self::SUCCESS;
    }
}
