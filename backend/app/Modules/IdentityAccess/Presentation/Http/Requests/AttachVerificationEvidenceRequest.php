<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class AttachVerificationEvidenceRequest extends FormRequest
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
            'type_doc' => ['required', 'string', 'max:64'],
            'fichier' => ['required', 'file', 'max:8192'],
            'numero' => ['nullable', 'string', 'max:191'],
            'autorite' => ['nullable', 'string', 'max:512'],
            'delivre_le' => ['nullable', 'date'],
            'expire_le' => ['nullable', 'date', 'after:delivre_le'],
        ];
    }

    /**
     * @return array{type_doc: string, numero: ?string, autorite: ?string, delivre_le: ?string, expire_le: ?string}
     */
    public function payload(): array
    {
        return [
            'type_doc' => $this->string('type_doc')->toString(),
            'numero' => $this->filled('numero') ? trim($this->string('numero')->toString()) : null,
            'autorite' => $this->filled('autorite') ? trim($this->string('autorite')->toString()) : null,
            'delivre_le' => $this->filled('delivre_le') ? $this->string('delivre_le')->toString() : null,
            'expire_le' => $this->filled('expire_le') ? $this->string('expire_le')->toString() : null,
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

    public function correlationId(): string
    {
        $id = $this->attributes->get('correlation_id');

        return is_string($id) && IdentityUlid::isValid($id) ? $id : IdentityUlid::make();
    }
}
