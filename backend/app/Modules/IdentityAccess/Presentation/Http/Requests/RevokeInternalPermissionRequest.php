<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class RevokeInternalPermissionRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:8', 'max:4000'],
        ];
    }

    /**
     * @return array{motif: string}
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'motif' => trim((string) $validated['motif']),
        ];
    }

    public function idempotencyKey(Account $account, string $attributionId): string
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

        return 'permissions_internes:revoke:idk:'.$account->id.':'.$attributionId.':'.$key;
    }

    public function intentionFingerprint(string $attributionId): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'action' => 'revocation',
            'attribution_id' => $attributionId,
            'motif' => $payload['motif'],
        ], JSON_THROW_ON_ERROR));
    }

    public function attributionId(string $id): string
    {
        $attributionId = strtoupper($id);

        if (! IdentityUlid::isValid($attributionId)) {
            throw new IdentityAccessException('habilitation_inconnue', 'Cette habilitation n est pas visible.', 404);
        }

        return $attributionId;
    }
}
