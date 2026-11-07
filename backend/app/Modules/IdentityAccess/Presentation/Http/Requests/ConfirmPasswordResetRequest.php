<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPasswordResetRequest extends FormRequest
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
            'defi_id' => ['required', 'string', 'size:26'],
            'preuve' => ['required', 'string', 'max:128'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }
}
