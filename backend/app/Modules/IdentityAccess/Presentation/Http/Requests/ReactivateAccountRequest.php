<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class ReactivateAccountRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'demande_id' => ['required', 'string', 'min:8', 'max:64'],
            'motif' => ['required', 'string', 'min:8', 'max:4000'],
            'avis_securite' => ['sometimes', 'nullable', 'string', 'min:8', 'max:4000'],
        ];
    }

    /**
     * @return array{demande_id: string, motif: string, avis_securite: ?string}
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $avis = isset($validated['avis_securite']) ? trim((string) $validated['avis_securite']) : '';

        return [
            'demande_id' => trim((string) $validated['demande_id']),
            'motif' => trim((string) $validated['motif']),
            'avis_securite' => $avis === '' ? null : $avis,
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

        return 'a1-23:reactivation:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(string $accountId): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'compte_id' => $accountId,
            'action' => 'reactivation',
            'demande_id' => $payload['demande_id'],
            'motif' => $payload['motif'],
            'avis_securite' => $payload['avis_securite'],
        ], JSON_THROW_ON_ERROR));
    }

    public function targetAccountId(string $id): string
    {
        $accountId = strtoupper($id);

        if (! IdentityUlid::isValid($accountId)) {
            throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
        }

        return $accountId;
    }
}
