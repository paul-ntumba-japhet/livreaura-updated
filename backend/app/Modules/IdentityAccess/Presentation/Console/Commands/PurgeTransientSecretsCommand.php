<?php

namespace App\Modules\IdentityAccess\Presentation\Console\Commands;

use App\Modules\IdentityAccess\Application\Actions\Messaging\PurgeTransientTransmissionSecrets;
use Illuminate\Console\Command;

class PurgeTransientSecretsCommand extends Command
{
    protected $signature = 'a1:purge-transient-secrets {--limit=50 : Nombre maximal de transmissions purgees}';

    protected $description = 'Met a NULL le contenu chiffre temporaire apres envoi certain, consommation, revocation ou echeance.';

    public function handle(PurgeTransientTransmissionSecrets $purge): int
    {
        $purged = $purge((int) $this->option('limit'));

        $this->info(count($purged).' secret(s) temporaire(s) purge(s).');

        return self::SUCCESS;
    }
}
