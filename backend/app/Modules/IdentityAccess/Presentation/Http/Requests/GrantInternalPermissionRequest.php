<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class GrantInternalPermissionRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'compte_id' => ['required', 'string', 'min:8', 'max:64'],
            'permission' => ['required', 'string', 'min:3', 'max:64'],
            'motif' => ['required', 'string', 'min:8', 'max:4000'],
            'expire_a' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array{compte_id: string, permission: string, motif: string, expire_a: ?string}
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $accountId = strtoupper(trim((string) $validated['compte_id']));

        if (! IdentityUlid::isValid($accountId)) {
            throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
        }

        $expire = isset($validated['expire_a']) ? trim((string) $validated['expire_a']) : '';

        return [
            'compte_id' => $accountId,
            'permission' => trim((string) $validated['permission']),
            'motif' => trim((string) $validated['motif']),
            'expire_a' => $expire !== '' ? $expire : null,
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

        return 'permissions_internes:grant:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'action' => 'attribution',
            'compte_id' => $payload['compte_id'],
            'permission' => $payload['permission'],
            'motif' => $payload['motif'],
            'expire_a' => $payload['expire_a'],
        ], JSON_THROW_ON_ERROR));
    }
}
