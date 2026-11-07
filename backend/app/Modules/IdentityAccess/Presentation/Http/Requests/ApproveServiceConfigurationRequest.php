<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class ApproveServiceConfigurationRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:3', 'max:64'],
            'valeurs' => ['required', 'array'],
            'effet_a' => ['required', 'string', 'max:64'],
        ];
    }

    /**
     * @return array{code: string, valeurs: array<string, mixed>, effet_a: string}
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'code' => trim((string) $validated['code']),
            'valeurs' => is_array($validated['valeurs']) ? $validated['valeurs'] : [],
            'effet_a' => trim((string) $validated['effet_a']),
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

        return 'configurations_service:approve:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'action' => 'approbation',
            'code' => $payload['code'],
            'valeurs' => $payload['valeurs'],
            'effet_a' => $payload['effet_a'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
