<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class DecideOrganizationVerificationRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resultat' => ['required', 'in:approved,needs_fix,rejected'],
            'motif' => ['required', 'string', 'min:8', 'max:4000'],
        ];
    }

    /**
     * @return array{resultat: string, motif: string}
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'resultat' => (string) $validated['resultat'],
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

        return 'a1-12:decision:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(string $dossierId): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'dossier_id' => $dossierId,
            'action' => 'decision',
            'resultat' => $payload['resultat'],
            'motif' => $payload['motif'],
        ], JSON_THROW_ON_ERROR));
    }
}
