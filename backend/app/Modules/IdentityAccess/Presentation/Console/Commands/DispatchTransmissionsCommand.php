<?php

namespace App\Modules\IdentityAccess\Presentation\Console\Commands;

use App\Modules\IdentityAccess\Application\Actions\Messaging\DispatchDueTransmissions;
use Illuminate\Console\Command;

class DispatchTransmissionsCommand extends Command
{
    protected $signature = 'a1:dispatch-transmissions {--limit=50 : Nombre maximal de transmissions prises en charge}';

    protected $description = 'Prend en charge les transmissions e-mail eligibles et dispatche leur envoi.';

    public function handle(DispatchDueTransmissions $dispatch): int
    {
        $claimed = $dispatch((int) $this->option('limit'));

        $this->info(count($claimed).' transmission(s) prise(s) en charge.');

        return self::SUCCESS;
    }
}
