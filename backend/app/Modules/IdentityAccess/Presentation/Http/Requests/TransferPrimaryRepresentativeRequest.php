<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class TransferPrimaryRepresentativeRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nouveau_compte_id' => ['required', 'string', 'size:26'],
            'motif' => ['required', 'string', 'min:8', 'max:4000'],
            'depart' => ['sometimes', 'boolean'],
            'demande_id' => ['sometimes', 'nullable', 'string', 'size:26'],
        ];
    }

    /**
     * @return array{nouveau_compte_id: string, motif: string, depart: bool, demande_id: ?string}
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $nouveau = strtoupper(trim((string) $validated['nouveau_compte_id']));
        $demande = isset($validated['demande_id']) ? strtoupper(trim((string) $validated['demande_id'])) : '';

        if (! IdentityUlid::isValid($nouveau)) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'Le nouveau representant est invalide.',
                422,
                ['nouveau_compte_id' => ['Identifiant invalide.']],
            );
        }

        if ($demande !== '' && ! IdentityUlid::isValid($demande)) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'La demande de transfert est invalide.',
                422,
                ['demande_id' => ['Identifiant invalide.']],
            );
        }

        return [
            'nouveau_compte_id' => $nouveau,
            'motif' => trim((string) $validated['motif']),
            'depart' => (bool) ($validated['depart'] ?? false),
            'demande_id' => $demande === '' ? null : $demande,
        ];
    }

    public function idempotencyKey(Account $account): string
    {
        $key = $this->headers->get('Idempotency-Key');

        if (! is_string($key) || strlen($key) < 8 || strlen($key) > 128) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'En-tete Idempotency-Key requis.',
                422,
                ['Idempotency-Key' => ['En-tete Idempotency-Key invalide.']],
            );
        }

        return 'a1-15:transfert:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(string $organizationId): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'organisation_id' => $organizationId,
            'action' => 'transfert_principal',
            'nouveau_compte_id' => $payload['nouveau_compte_id'],
            'motif' => $payload['motif'],
            'depart' => $payload['depart'],
            'demande_id' => $payload['demande_id'],
        ], JSON_THROW_ON_ERROR));
    }
}
