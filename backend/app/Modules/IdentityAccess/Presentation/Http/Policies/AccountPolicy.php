<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Policies;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class AccountPolicy
{
    public function view(Account $actor, Account $account): bool
    {
        return $actor->id === $account->id;
    }

    public function update(Account $actor, Account $account): bool
    {
        return $actor->id === $account->id;
    }
}
