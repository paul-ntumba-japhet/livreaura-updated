<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class AuthorizeAppealSessionRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'compte_id' => ['required', 'string', 'size:26'],
            'motif' => ['required', 'string', 'min:8', 'max:4000'],
            'methode_verification' => ['required', 'string', 'min:4', 'max:64'],
        ];
    }

    /**
     * @return array{compte_id: string, motif: string, methode_verification: string}
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $accountId = strtoupper(trim((string) $validated['compte_id']));

        if (! IdentityUlid::isValid($accountId)) {
            throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
        }

        return [
            'compte_id' => $accountId,
            'motif' => trim((string) $validated['motif']),
            'methode_verification' => trim((string) $validated['methode_verification']),
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

        return 'a1-25:autorisation:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(string $demandKey): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'demande' => strtoupper($demandKey),
            'compte_id' => $payload['compte_id'],
            'motif' => $payload['motif'],
            'methode_verification' => $payload['methode_verification'],
        ], JSON_THROW_ON_ERROR));
    }
}
