<?php

namespace App\Modules\IdentityAccess\Domain\Support;

final class PhoneNormalizer
{
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $trimmed = trim($phone);

        if ($trimmed === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d+]/', '', $trimmed);

        return is_string($normalized) && $normalized !== '' ? $normalized : null;
    }
}
