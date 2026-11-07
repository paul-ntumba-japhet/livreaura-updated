<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;

class AssignServiceSubstituteRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'titulaire_id' => ['required', 'string', 'min:8', 'max:64'],
            'suppleant_id' => ['required', 'string', 'min:8', 'max:64'],
            'fonction' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,31}$/'],
            'debut_a' => ['required', 'string', 'max:64'],
            'fin_a' => ['required', 'string', 'max:64'],
            'motif' => ['required', 'string', 'min:8', 'max:4000'],
        ];
    }

    /**
     * @return array{titulaire_id: string, suppleant_id: string, fonction: string, debut_a: string, fin_a: string, motif: string}
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $holderId = strtoupper(trim((string) $validated['titulaire_id']));
        $substituteId = strtoupper(trim((string) $validated['suppleant_id']));

        if (! IdentityUlid::isValid($holderId) || ! IdentityUlid::isValid($substituteId)) {
            throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
        }

        return [
            'titulaire_id' => $holderId,
            'suppleant_id' => $substituteId,
            'fonction' => trim((string) $validated['fonction']),
            'debut_a' => trim((string) $validated['debut_a']),
            'fin_a' => trim((string) $validated['fin_a']),
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

        return 'suppleances_service:assign:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(): string
    {
        $payload = $this->payload();

        return SecretHasher::hmac(json_encode([
            'action' => 'suppleance',
            'titulaire_id' => $payload['titulaire_id'],
            'suppleant_id' => $payload['suppleant_id'],
            'fonction' => $payload['fonction'],
            'debut_a' => $payload['debut_a'],
            'fin_a' => $payload['fin_a'],
            'motif' => $payload['motif'],
        ], JSON_THROW_ON_ERROR));
    }
}
