<?php

namespace App\Modules\IdentityAccess\Domain\Support;

final class SecretHasher
{
    public static function hmac(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    public static function equals(string $knownHmac, string $plain): bool
    {
        return hash_equals($knownHmac, self::hmac($plain));
    }
}
