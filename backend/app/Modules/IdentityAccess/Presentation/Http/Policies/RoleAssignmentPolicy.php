<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Policies;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountRole;

class RoleAssignmentPolicy
{
    public function view(Account $account, AccountRole $assignment): bool
    {
        return $account->id === $assignment->compte_id;
    }

    public function revoke(Account $account, AccountRole $assignment): bool
    {
        return $account->id === $assignment->compte_id;
    }
}
