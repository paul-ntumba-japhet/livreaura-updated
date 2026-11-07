<?php

namespace App\Modules\IdentityAccess\Application\Actions\Registration;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class PrepareAccountVerification
{
    public function __construct(private IssueVerificationChallenge $issue) {}

    public function __invoke(Account $account): void
    {
        $account->refresh();

        if ($account->statut !== AccountStatus::Pending) {
            return;
        }

        ($this->issue)($account);
    }
}
