<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Policies;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;

class OrganizationMembershipPolicy
{
    public function view(Account $account, OrganizationMembership $membership): bool
    {
        return $account->id === $membership->compte_id;
    }

    public function revoke(Account $account, OrganizationMembership $membership): bool
    {
        return $account->id === $membership->compte_id;
    }
}
