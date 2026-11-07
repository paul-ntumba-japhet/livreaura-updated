<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class CanAccessAccountBackoffice
{
    public function __construct(
        private HasInternalPermission $hasPermission,
    ) {}

    public function __invoke(Account $account): bool
    {
        return ($this->hasPermission)($account, 'suspendre_compte')
            || ($this->hasPermission)($account, 'reactiver_compte')
            || ($this->hasPermission)($account, 'cloturer_compte')
            || ($this->hasPermission)($account, 'traiter_support');
    }
}
