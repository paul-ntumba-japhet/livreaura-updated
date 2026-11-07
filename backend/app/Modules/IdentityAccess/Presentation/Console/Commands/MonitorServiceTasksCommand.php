<?php

namespace App\Modules\IdentityAccess\Presentation\Console\Commands;

use App\Modules\IdentityAccess\Application\Actions\Tasks\MonitorOverdueServiceTasks;
use Illuminate\Console\Command;

class MonitorServiceTasksCommand extends Command
{
    protected $signature = 'a1:monitor-service-tasks {--limit=50 : Nombre maximal de taches relues}';

    protected $description = 'Constate les retards de taches et enregistre une escalade, sans rejet automatique.';

    public function handle(MonitorOverdueServiceTasks $monitor): int
    {
        $escalated = $monitor((int) $this->option('limit'));

        $this->info(count($escalated).' tache(s) escaladee(s).');

        return self::SUCCESS;
    }
}
