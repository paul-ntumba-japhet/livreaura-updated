<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class SuspendAccountRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:8', 'max:4000'],
            'fin_prevue' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array{motif: string, fin_prevue: ?string}
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $deadline = isset($validated['fin_prevue']) ? trim((string) $validated['fin_prevue']) : '';

        return [
            'motif' => trim((string) $validated['motif']),
            'fin_prevue' => $deadline === '' ? null : $deadline,
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

        return 'a1-22:suspension:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(string $accountId): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'compte_id' => $accountId,
            'action' => 'suspension',
            'motif' => $payload['motif'],
            'fin_prevue' => $payload['fin_prevue'],
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
