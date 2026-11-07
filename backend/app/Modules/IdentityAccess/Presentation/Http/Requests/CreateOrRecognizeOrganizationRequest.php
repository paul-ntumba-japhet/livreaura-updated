<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\OrganizationName;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrRecognizeOrganizationRequest extends FormRequest
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
            'type_organisation_id' => ['required', 'string', 'size:26'],
            'raison_sociale' => ['required', 'string', 'max:512'],
            'pays' => ['required', 'string', 'size:2'],
            'ville' => ['nullable', 'string', 'max:512'],
            'email_pro' => ['nullable', 'email', 'max:320'],
            'site' => ['nullable', 'string', 'max:512'],
            'organisation_id' => ['nullable', 'string', 'size:26'],
            'confirmer_nouvelle' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array{type_organisation_id: string, raison_sociale: string, pays: string, ville: ?string, email_pro: ?string, site: ?string, organisation_id: ?string, confirmer_nouvelle: bool}
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
            'type_organisation_id' => $this->string('type_organisation_id')->toString(),
            'raison_sociale' => trim($this->string('raison_sociale')->toString()),
            'pays' => strtoupper($this->string('pays')->toString()),
            'ville' => $this->filled('ville') ? trim($this->string('ville')->toString()) : null,
            'email_pro' => $this->filled('email_pro') ? strtolower(trim($this->string('email_pro')->toString())) : null,
            'site' => $this->filled('site') ? trim($this->string('site')->toString()) : null,
            'organisation_id' => is_string($organizationId) && $organizationId !== '' ? $organizationId : null,
            'confirmer_nouvelle' => $this->boolean('confirmer_nouvelle'),
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

        return 'a1-10:idk:'.$account->id.':'.$key;
    }

    public function intentionFingerprint(): string
    {
        $payload = $this->payload();
        $canonical = json_encode([
            'type_organisation_id' => $payload['type_organisation_id'],
            'raison_sociale' => OrganizationName::normalize($payload['raison_sociale']),
            'pays' => $payload['pays'],
            'organisation_id' => $payload['organisation_id'] ?? 'nouvelle',
            'confirmer_nouvelle' => $payload['confirmer_nouvelle'],
        ], JSON_THROW_ON_ERROR);

        return SecretHasher::hmac($canonical);
    }

    public function correlationId(): string
    {
        $id = $this->attributes->get('correlation_id');

        return is_string($id) && IdentityUlid::isValid($id) ? $id : IdentityUlid::make();
    }
}
