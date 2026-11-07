<?php

namespace App\Modules\IdentityAccess\Application\DTOs;

final class BootstrapInstallResult
{
    /**
     * @param  list<string>  $createdTextTypes
     * @param  list<string>  $createdConfigCodes
     * @param  list<string>  $grantedPermissions
     */
    public function __construct(
        public readonly string $accountId,
        public readonly string $email,
        public readonly bool $accountCreated,
        public readonly array $createdTextTypes,
        public readonly array $createdConfigCodes,
        public readonly array $grantedPermissions,
    ) {}
}
