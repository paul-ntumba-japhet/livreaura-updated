<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class CanAccessOrganizationBackoffice
{
    public function __construct(
        private HasInternalPermission $hasPermission,
    ) {}

    public function __invoke(Account $account): bool
    {
        return ($this->hasPermission)($account, 'transferer_principal')
            || ($this->hasPermission)($account, 'verifier_representation')
            || ($this->hasPermission)($account, 'traiter_support');
    }
}
