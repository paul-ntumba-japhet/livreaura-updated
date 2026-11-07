<?php

namespace App\Modules\IdentityAccess\Domain\Exceptions;

class InvalidChallenge extends IdentityAccessException
{
    public static function rejected(): self
    {
        return new self(
            'defi_invalide',
            'Le defi est invalide, expire ou deja consomme.',
            422,
        );
    }

    public static function ineligibleAccount(): self
    {
        return new self(
            'compte_ineligible',
            'Ce compte ne peut pas etre active.',
            409,
        );
    }
}
