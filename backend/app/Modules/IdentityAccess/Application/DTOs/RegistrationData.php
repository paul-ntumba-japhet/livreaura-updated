<?php

namespace App\Modules\IdentityAccess\Application\DTOs;

final class RegistrationData
{
    /**
     * @param  list<array{texte_version_id: string, nature: string}>  $consents
     */
    public function __construct(
        public readonly string $path,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly ?string $phone,
        public readonly string $locale,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $penName,
        public readonly string $country,
        public readonly ?string $city,
        public readonly ?string $writingLanguage,
        public readonly array $consents,
        public readonly string $idempotencyKey,
        public readonly string $intentionFingerprint,
        public readonly string $correlationId,
    ) {}
}
