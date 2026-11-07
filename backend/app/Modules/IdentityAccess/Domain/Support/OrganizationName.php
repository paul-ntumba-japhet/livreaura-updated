<?php

namespace App\Modules\IdentityAccess\Domain\Support;

final class OrganizationName
{
    public static function normalize(string $value): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($collapsed);
    }
}
