<?php

namespace App\Modules\IdentityAccess\Domain\Exceptions;

class AuthenticationDenied extends IdentityAccessException
{
    public static function generic(): self
    {
        return new self(
            'authentification_refusee',
            'Identifiants invalides ou compte ineligible.',
            401,
        );
    }
}
