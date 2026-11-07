<?php

namespace App\Modules\IdentityAccess\Domain\Support;

use Illuminate\Support\Str;

final class IdentityUlid
{
    public static function make(): string
    {
        return strtoupper((string) Str::ulid());
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match('/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/', $value);
    }
}
