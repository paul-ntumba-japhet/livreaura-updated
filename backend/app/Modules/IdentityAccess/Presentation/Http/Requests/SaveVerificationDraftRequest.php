<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Requests;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use Illuminate\Foundation\Http\FormRequest;

class SaveVerificationDraftRequest extends FormRequest
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
            'profil_fiscal' => ['nullable', 'array'],
            'profil_fiscal.identifiant_fiscal' => ['nullable', 'string', 'max:512'],
            'profil_fiscal.pays_fiscal' => ['required_with:profil_fiscal', 'string', 'size:2'],
            'profil_fiscal.nom_legal' => ['required_with:profil_fiscal', 'string', 'max:512'],
            'compte_reversement' => ['nullable', 'array'],
            'compte_reversement.canal' => ['required_with:compte_reversement', 'string', 'max:64'],
            'compte_reversement.prestataire' => ['nullable', 'string', 'max:512'],
            'compte_reversement.titulaire' => ['required_with:compte_reversement', 'string', 'max:512'],
            'compte_reversement.pays' => ['required_with:compte_reversement', 'string', 'size:2'],
            'compte_reversement.devise' => ['required_with:compte_reversement', 'string', 'size:3'],
            'compte_reversement.reference_prestataire' => ['required_with:compte_reversement', 'string', 'max:191'],
            'compte_reversement.affichage_masque' => ['required_with:compte_reversement', 'string', 'max:512'],
        ];
    }

    /**
     * @return array{profil_fiscal: ?array{identifiant_fiscal: ?string, pays_fiscal: string, nom_legal: string}, compte_reversement: ?array{canal: string, prestataire: ?string, titulaire: string, pays: string, devise: string, reference_prestataire: ?string, affichage_masque: string}}
     */
    public function payload(): array
    {
        return [
            'profil_fiscal' => $this->filled('profil_fiscal') ? [
                'identifiant_fiscal' => $this->filled('profil_fiscal.identifiant_fiscal')
                    ? trim($this->string('profil_fiscal.identifiant_fiscal')->toString())
                    : null,
                'pays_fiscal' => strtoupper($this->string('profil_fiscal.pays_fiscal')->toString()),
                'nom_legal' => trim($this->string('profil_fiscal.nom_legal')->toString()),
            ] : null,
            'compte_reversement' => $this->filled('compte_reversement') ? [
                'canal' => $this->string('compte_reversement.canal')->toString(),
                'prestataire' => $this->filled('compte_reversement.prestataire')
                    ? trim($this->string('compte_reversement.prestataire')->toString())
                    : null,
                'titulaire' => trim($this->string('compte_reversement.titulaire')->toString()),
                'pays' => strtoupper($this->string('compte_reversement.pays')->toString()),
                'devise' => strtoupper($this->string('compte_reversement.devise')->toString()),
                'reference_prestataire' => trim($this->string('compte_reversement.reference_prestataire')->toString()),
                'affichage_masque' => trim($this->string('compte_reversement.affichage_masque')->toString()),
            ] : null,
        ];
    }

    public function correlationId(): string
    {
        $id = $this->attributes->get('correlation_id');

        return is_string($id) && IdentityUlid::isValid($id) ? $id : IdentityUlid::make();
    }
}
