<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Enums\RegistrationPath;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\EmailNormalizer;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\PhoneNormalizer;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterAccountRequest extends FormRequest
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
            'parcours' => ['required', Rule::enum(RegistrationPath::class)],
            'email' => ['required', 'email', 'max:320'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:64'],
            'prenom' => ['required', 'string', 'max:512'],
            'nom' => ['required', 'string', 'max:512'],
            'nom_plume' => ['nullable', 'string', 'max:512'],
            'pays' => ['required', 'string', 'size:2'],
            'ville' => ['nullable', 'string', 'max:512'],
            'langue_ecriture' => ['nullable', 'string', Rule::in(['fr', 'en', 'lua', 'ln', 'sw', 'kg'])],
            'consentements' => ['present', 'array'],
            'consentements.*.texte_version_id' => ['required', 'string', 'size:26'],
            'consentements.*.nature' => ['required', 'in:acceptation,prise_connaissance,consentement_optionnel'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'langue_ecriture.in' => 'Choisissez une langue dans la liste.',
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

        return 'a1-02:idk:'.$key;
    }

    public function intentionFingerprint(): string
    {
        $canonical = json_encode([
            'parcours' => $this->string('parcours')->toString(),
            'email' => EmailNormalizer::normalize($this->string('email')->toString()),
            'prenom' => $this->string('prenom')->toString(),
            'nom' => $this->string('nom')->toString(),
            'pays' => strtoupper($this->string('pays')->toString()),
            'consentements' => collect($this->input('consentements', []))
                ->map(fn ($item): array => [
                    'texte_version_id' => $item['texte_version_id'] ?? '',
                    'nature' => $item['nature'] ?? '',
                ])
                ->sortBy('texte_version_id')
                ->values()
                ->all(),
        ], JSON_THROW_ON_ERROR);

        return SecretHasher::hmac($canonical);
    }

    public function normalizedEmail(): string
    {
        return EmailNormalizer::normalize($this->string('email')->toString());
    }

    public function normalizedPhone(): ?string
    {
        return PhoneNormalizer::normalize($this->input('telephone'));
    }

    public function correlationId(): string
    {
        $id = $this->attributes->get('correlation_id');

        return is_string($id) && IdentityUlid::isValid($id) ? $id : IdentityUlid::make();
    }
}
