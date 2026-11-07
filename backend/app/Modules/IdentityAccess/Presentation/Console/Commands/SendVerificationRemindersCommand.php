<?php

namespace App\Modules\IdentityAccess\Presentation\Console\Commands;

use App\Modules\IdentityAccess\Application\Actions\Verification\SendVerificationReminders;
use Illuminate\Console\Command;

class SendVerificationRemindersCommand extends Command
{
    protected $signature = 'a1:send-verification-reminders {--limit=50 : Nombre maximal d habilitations relues}';

    protected $description = 'Envoie les rappels J-30, J-7 et J-1 sans retirer valid.';

    public function handle(SendVerificationReminders $reminders): int
    {
        $recorded = $reminders((int) $this->option('limit'));

        $this->info(count($recorded).' rappel(s) enregistre(s).');

        return self::SUCCESS;
    }
}
