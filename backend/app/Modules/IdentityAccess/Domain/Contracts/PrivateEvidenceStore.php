<?php

namespace App\Modules\IdentityAccess\Domain\Contracts;

final class StoredPrivateEvidence
{
    public function __construct(
        public readonly string $reference,
        public readonly string $sha256,
        public readonly int $bytes,
        public readonly string $detectedMime,
    ) {}
}

interface PrivateEvidenceStore
{
    public function store(string $contents, string $declaredMime, string $originalName): StoredPrivateEvidence;

    public function exists(string $reference): bool;

    public function hashOf(string $reference): ?string;

    public function read(string $reference): ?string;
}
