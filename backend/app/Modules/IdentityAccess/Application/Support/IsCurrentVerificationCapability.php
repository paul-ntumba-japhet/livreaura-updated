<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;

class IsCurrentVerificationCapability
{
    public function __invoke(?VerificationCapability $capability): bool
    {
        if ($capability === null) {
            return false;
        }

        if (! in_array($capability->etat, ['valid', 'a_renouveler'], true)) {
            return false;
        }

        if ($capability->valide_jusqua !== null && $capability->valide_jusqua->lte(now())) {
            return false;
        }

        return true;
    }
}
