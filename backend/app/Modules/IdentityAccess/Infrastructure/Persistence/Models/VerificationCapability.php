<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationCapability extends Model
{
    protected $table = 'habilitations_verification';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'sujet_id',
        'niveau',
        'dossier_courant_id',
        'decision_courante_id',
        'etat',
        'motif_restriction',
    ];

    protected function casts(): array
    {
        return [
            'obtenue_a' => 'datetime',
            'valide_jusqua' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(KycSubject::class, 'sujet_id', 'id');
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(VerificationDossier::class, 'dossier_courant_id', 'id');
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(VerificationDecision::class, 'decision_courante_id', 'id');
    }
}
