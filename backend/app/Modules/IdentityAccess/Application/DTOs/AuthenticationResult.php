<?php

namespace App\Modules\IdentityAccess\Application\DTOs;

use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;

final class AuthenticationResult
{
    public function __construct(
        public readonly Account $account,
        public readonly AuthenticationSession $session,
        public readonly SessionAccessScope $scope,
        public readonly string $reference,
    ) {}
}
