<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class ReplyToSupportRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contenu' => ['required', 'string', 'min:8', 'max:4000'],
            'visibilite' => ['nullable', 'string', 'in:demandeur,interne'],
        ];
    }

    /**
     * @return array{contenu: string, visibilite: string}
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $visibility = isset($validated['visibilite']) ? trim((string) $validated['visibilite']) : 'demandeur';

        return [
            'contenu' => trim((string) $validated['contenu']),
            'visibilite' => $visibility === 'interne' ? 'interne' : 'demandeur',
        ];
    }

    public function idempotencyKey(Account $account, string $demandKey): string
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

        return 'echanges_support:reply:idk:'.$account->id.':'.$demandKey.':'.$key;
    }

    public function intentionFingerprint(string $demandKey): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'action' => 'echange',
            'demande' => $demandKey,
            'contenu' => $payload['contenu'],
            'visibilite' => $payload['visibilite'],
        ], JSON_THROW_ON_ERROR));
    }
}
