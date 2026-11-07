<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

class RecordVerificationControlRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'controle_id' => ['required', 'string', 'size:26'],
            'resultat' => ['required', 'in:passed,failed,not_applicable'],
            'observation' => ['nullable', 'string', 'max:4000'],
            'justification_non_applicable' => ['nullable', 'string', 'max:4000'],
        ];
    }

    /**
     * @return array{controle_id: string, resultat: string, observation: ?string, justification_non_applicable: ?string}
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'controle_id' => (string) $validated['controle_id'],
            'resultat' => (string) $validated['resultat'],
            'observation' => isset($validated['observation']) ? (string) $validated['observation'] : null,
            'justification_non_applicable' => isset($validated['justification_non_applicable'])
                ? (string) $validated['justification_non_applicable']
                : null,
        ];
    }
}
