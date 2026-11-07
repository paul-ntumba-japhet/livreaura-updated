<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Foundation\Http\FormRequest;

class OpenVerificationDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'niveau' => ['required', 'string', 'max:64'],
            'organisation_id' => ['nullable', 'string', 'size:26'],
        ];
    }

    /**
     * @return array{niveau: string, organisation_id: ?string}
     */
    public function payload(): array
    {
        $organizationId = $this->input('organisation_id');

        if (is_string($organizationId) && $organizationId !== '' && ! IdentityUlid::isValid($organizationId)) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'Identifiant d organisation invalide.',
                422,
                ['organisation_id' => ['Identifiant d organisation invalide.']],
            );
        }

        return [
            'niveau' => $this->string('niveau')->toString(),
            'organisation_id' => is_string($organizationId) && $organizationId !== '' ? $organizationId : null,
        ];
    }

    public function idempotencyKey(Account $account): string
    {
        return $this->prefixedIdempotencyKey($account, 'a1-11');
    }

    public function intentionFingerprint(): string
    {
        $payload = $this->payload();
        $canonical = json_encode([
            'niveau' => $payload['niveau'],
            'organisation_id' => $payload['organisation_id'] ?? 'particulier',
        ], JSON_THROW_ON_ERROR);

        return SecretHasher::hmac($canonical);
    }

    public function correlationId(): string
    {
        $id = $this->attributes->get('correlation_id');

        return is_string($id) && IdentityUlid::isValid($id) ? $id : IdentityUlid::make();
    }

    protected function prefixedIdempotencyKey(Account $account, string $prefix): string
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

        return $prefix.':idk:'.$account->id.':'.$key;
    }
}
