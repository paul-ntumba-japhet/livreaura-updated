<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use Illuminate\Http\UploadedFile;

class AttachSupportEvidenceRequest extends BackofficeCorrelationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'min:3', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            'fichier' => ['required', 'file', 'max:8192'],
            'acces' => ['nullable', 'string', 'in:demandeur,interne'],
            'echange_id' => ['nullable', 'string', 'max:26'],
        ];
    }

    /**
     * @return array{type: string, acces: string, echange_id: ?string}
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $access = isset($validated['acces']) ? trim((string) $validated['acces']) : 'demandeur';
        $exchangeId = isset($validated['echange_id']) ? strtoupper(trim((string) $validated['echange_id'])) : '';

        if ($exchangeId !== '' && ! IdentityUlid::isValid($exchangeId)) {
            throw new IdentityAccessException('demande_inconnue', 'Cette demande n est pas visible.', 404);
        }

        return [
            'type' => trim((string) $validated['type']),
            'acces' => $access === 'interne' ? 'interne' : 'demandeur',
            'echange_id' => $exchangeId !== '' ? $exchangeId : null,
        ];
    }

    public function uploadedFile(): UploadedFile
    {
        $file = $this->file('fichier');

        if (! $file instanceof UploadedFile) {
            throw new IdentityAccessException(
                'fichier_irrecevable',
                'Un fichier est requis.',
                422,
                ['fichier' => ['Fichier requis.']],
            );
        }

        return $file;
    }
}
