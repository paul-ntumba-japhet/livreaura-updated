<?php

namespace App\Modules\IdentityAccess\Presentation\Console\Commands;

use App\Modules\IdentityAccess\Application\Actions\Verification\ReviewVerificationValidity;
use Illuminate\Console\Command;

class ReviewVerificationExpiriesCommand extends Command
{
    protected $signature = 'a1:review-expiries {--limit=50 : Nombre maximal d habilitations relues}';

    protected $description = 'Expire les habilitations dont l echeance est depassee, sans retablir un acces.';

    public function handle(ReviewVerificationValidity $review): int
    {
        $changed = $review((int) $this->option('limit'));

        $this->info(count($changed).' habilitation(s) expiree(s).');

        return self::SUCCESS;
    }
}
