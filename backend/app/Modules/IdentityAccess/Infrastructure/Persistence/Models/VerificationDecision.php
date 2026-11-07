<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationDecision extends Model
{
    protected $table = 'decisions_verification';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'dossier_id',
        'decideur_id',
        'resultat',
        'motif',
        'restrictions',
    ];

    protected function casts(): array
    {
        return [
            'decide_a' => 'datetime',
            'valide_jusqua' => 'datetime',
        ];
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(VerificationDossier::class, 'dossier_id', 'id');
    }
}
