<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Foundation\Http\FormRequest;

class EnableAuthorPathRequest extends FormRequest
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
            'auteur_id' => ['nullable', 'string', 'size:26'],
        ];
    }

    public function claimedAuthorId(): ?string
    {
        $value = $this->input('auteur_id');

        if (! is_string($value) || $value === '') {
            return null;
        }

        if (! IdentityUlid::isValid($value)) {
            throw new IdentityAccessException(
                'saisie_invalide',
                'Identifiant de fiche auteur invalide.',
                422,
                ['auteur_id' => ['Identifiant de fiche auteur invalide.']],
            );
        }

        return $value;
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

        return 'a1-08:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(): string
    {
        $canonical = json_encode([
            'auteur_id' => $this->claimedAuthorId() ?? 'nouvelle_fiche',
        ], JSON_THROW_ON_ERROR);

        return SecretHasher::hmac($canonical);
    }

    public function correlationId(): string
    {
        $id = $this->attributes->get('correlation_id');

        return is_string($id) && IdentityUlid::isValid($id) ? $id : IdentityUlid::make();
    }
}
