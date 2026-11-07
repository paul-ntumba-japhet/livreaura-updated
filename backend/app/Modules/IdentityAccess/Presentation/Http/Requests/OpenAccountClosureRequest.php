<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;

class OpenAccountClosureRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:320'],
            'message' => ['required', 'string', 'min:8', 'max:4000'],
        ];
    }

    /**
     * @return array{email: string, message: string}
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'email' => strtolower(trim((string) $validated['email'])),
            'message' => trim((string) $validated['message']),
        ];
    }

    public function idempotencyKey(): string
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

        return 'cloture:demande:idk:'.$key;
    }

    public function intentionFingerprint(): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'action' => 'cloture_demande',
            'email' => $payload['email'],
            'message' => $payload['message'],
        ], JSON_THROW_ON_ERROR));
    }
}
