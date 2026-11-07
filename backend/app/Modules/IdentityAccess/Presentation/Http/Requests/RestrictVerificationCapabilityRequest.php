<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class RestrictVerificationCapabilityRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'etat' => ['required', 'in:a_renouveler,restreint,revoked'],
            'motif' => ['required', 'string', 'min:8', 'max:4000'],
        ];
    }

    /**
     * @return array{etat: string, motif: string}
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'etat' => (string) $validated['etat'],
            'motif' => trim((string) $validated['motif']),
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

        return 'a1-14:restriction:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(string $capabilityId): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'habilitation_id' => $capabilityId,
            'action' => 'restriction',
            'etat' => $payload['etat'],
            'motif' => $payload['motif'],
        ], JSON_THROW_ON_ERROR));
    }
}
