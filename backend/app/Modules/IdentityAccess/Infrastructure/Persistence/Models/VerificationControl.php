<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VerificationControl extends Model
{
    protected $table = 'controles_verification';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'dossier_id',
        'exigence_id',
        'resultat',
        'observation',
        'justification_non_applicable',
        'controleur_id',
        'origine_systeme',
    ];

    protected function casts(): array
    {
        return [
            'controle_a' => 'datetime',
        ];
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(VerificationDossier::class, 'dossier_id', 'id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(VerificationRequirement::class, 'exigence_id', 'id');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            VerificationDocument::class,
            'controles_documents',
            'controle_id',
            'document_id',
        )->withPivot('dossier_id');
    }
}
