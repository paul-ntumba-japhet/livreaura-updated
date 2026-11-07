<?php

namespace App\Modules\IdentityAccess\Domain\Support;

final class DestinationMasker
{
    public static function email(string $email): string
    {
        $parts = explode('@', $email, 2);

        if (count($parts) !== 2) {
            return '***';
        }

        [$local, $domain] = $parts;
        $first = mb_substr($local, 0, 1);

        return $first.'***@'.$domain;
    }
}
