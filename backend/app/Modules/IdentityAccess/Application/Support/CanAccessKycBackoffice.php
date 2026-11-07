<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class CanAccessKycBackoffice
{
    public function __construct(
        private HasInternalPermission $hasPermission,
    ) {}

    public function __invoke(Account $account): bool
    {
        return ($this->hasPermission)($account, 'controler_kyc')
            || ($this->hasPermission)($account, 'decider_kyc')
            || ($this->hasPermission)($account, 'decider_kyc_commercial')
            || ($this->hasPermission)($account, 'affecter_taches')
            || ($this->hasPermission)($account, 'restreindre_habilitation');
    }
}
