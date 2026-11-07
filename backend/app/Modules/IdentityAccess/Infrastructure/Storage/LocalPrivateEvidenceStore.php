<?php

namespace App\Modules\IdentityAccess\Infrastructure\Storage;

use App\Modules\IdentityAccess\Domain\Contracts\PrivateEvidenceStore;
use App\Modules\IdentityAccess\Domain\Contracts\StoredPrivateEvidence;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use Illuminate\Support\Facades\Storage;

class LocalPrivateEvidenceStore implements PrivateEvidenceStore
{
    private const MAX_BYTES = 8_388_608;

    /**
     * @var array<string, string>
     */
    private const MAGIC = [
        '%PDF' => 'application/pdf',
        "\xFF\xD8\xFF" => 'image/jpeg',
        "\x89PNG\r\n\x1A\n" => 'image/png',
    ];

    public function store(string $contents, string $declaredMime, string $originalName): StoredPrivateEvidence
    {
        $bytes = strlen($contents);

        if ($bytes < 8 || $bytes > self::MAX_BYTES) {
            throw new IdentityAccessException(
                'fichier_irrecevable',
                'Ce fichier depasse la taille autorisee ou est vide.',
                422,
                ['fichier' => ['Fichier irrecevable.']],
            );
        }

        $detected = $this->detectMime($contents);
        $declared = strtolower(trim($declaredMime));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($detected === null || ! $this->extensionMatches($detected, $extension)) {
            throw new IdentityAccessException(
                'fichier_irrecevable',
                'Ce format de fichier n est pas recevable.',
                422,
                ['fichier' => ['Format de fichier irrecevable.']],
            );
        }

        if ($declared !== '' && $declared !== $detected && $declared !== 'application/octet-stream') {
            throw new IdentityAccessException(
                'fichier_irrecevable',
                'Ce format de fichier n est pas recevable.',
                422,
                ['fichier' => ['Format de fichier irrecevable.']],
            );
        }

        $reference = IdentityUlid::make();
        Storage::disk('kyc')->put($reference, $contents);

        return new StoredPrivateEvidence(
            reference: $reference,
            sha256: hash('sha256', $contents),
            bytes: $bytes,
            detectedMime: $detected,
        );
    }

    public function exists(string $reference): bool
    {
        return $reference !== '' && Storage::disk('kyc')->exists($reference);
    }

    public function hashOf(string $reference): ?string
    {
        $contents = $this->read($reference);

        return $contents === null ? null : hash('sha256', $contents);
    }

    public function read(string $reference): ?string
    {
        if (! $this->exists($reference)) {
            return null;
        }

        $contents = Storage::disk('kyc')->get($reference);

        return is_string($contents) ? $contents : null;
    }

    private function detectMime(string $contents): ?string
    {
        foreach (self::MAGIC as $magic => $mime) {
            if (str_starts_with($contents, $magic)) {
                return $mime;
            }
        }

        return null;
    }

    private function extensionMatches(string $mime, string $extension): bool
    {
        return match ($mime) {
            'application/pdf' => $extension === 'pdf',
            'image/jpeg' => in_array($extension, ['jpg', 'jpeg'], true),
            'image/png' => $extension === 'png',
            default => false,
        };
    }
}
