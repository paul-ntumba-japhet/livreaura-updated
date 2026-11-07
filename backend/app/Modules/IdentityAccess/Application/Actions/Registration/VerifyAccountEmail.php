<?php

namespace App\Modules\IdentityAccess\Application\Actions\Registration;

class VerifyAccountEmail
{
    public function __construct(private ActivateAccount $activate) {}

    public function __invoke(string $challengeId, string $proof, string $correlationId): mixed
    {
        return ($this->activate)($challengeId, $proof, $correlationId);
    }
}
