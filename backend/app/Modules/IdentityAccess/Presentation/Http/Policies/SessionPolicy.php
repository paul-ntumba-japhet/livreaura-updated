<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Policies;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;

class SessionPolicy
{
    public function view(Account $account, AuthenticationSession $session): bool
    {
        return $account->id === $session->compte_id;
    }

    public function revoke(Account $account, AuthenticationSession $session): bool
    {
        return $account->id === $session->compte_id;
    }
}
