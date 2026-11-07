<?php

namespace App\Modules\IdentityAccess\Domain\Contracts;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

interface IdentityNotificationSender
{
    public function sendEmailActivation(Account $account, string $challengeId, string $secret): void;

    public function sendPasswordReset(Account $account, string $challengeId, string $secret): void;

    public function sendPasswordChanged(Account $account): void;
}
