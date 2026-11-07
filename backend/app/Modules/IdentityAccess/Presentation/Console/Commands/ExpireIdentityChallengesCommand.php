<?php

namespace App\Modules\IdentityAccess\Presentation\Console\Commands;

use App\Modules\IdentityAccess\Application\Actions\Messaging\ExpireIdentityChallenges;
use Illuminate\Console\Command;

class ExpireIdentityChallengesCommand extends Command
{
    protected $signature = 'a1:expire-challenges {--limit=50 : Nombre maximal de defis relus}';

    protected $description = 'Annule les transmissions devenues inutiles et purge les secrets des defis echus.';

    public function handle(ExpireIdentityChallenges $expire): int
    {
        $expired = $expire((int) $this->option('limit'));

        $this->info(count($expired).' defi(s) echu(s) repris.');

        return self::SUCCESS;
    }
}
