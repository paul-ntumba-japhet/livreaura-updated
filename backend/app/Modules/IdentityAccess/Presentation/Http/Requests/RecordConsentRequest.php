<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordConsentRequest extends FormRequest
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
            'texte_version_id' => ['required', 'string', 'size:26'],
            'action' => ['sometimes', 'in:acceptation,retrait'],
            'nature' => ['required_unless:action,retrait', 'nullable', 'in:acceptation,prise_connaissance,consentement_optionnel'],
        ];
    }

    public function action(): string
    {
        $action = $this->input('action', 'acceptation');

        return is_string($action) && $action !== '' ? $action : 'acceptation';
    }
}
