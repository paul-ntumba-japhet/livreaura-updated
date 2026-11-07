<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationEvent extends Model
{
    protected $table = 'evenements_verification';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'dossier_id',
        'habilitation_id',
        'controle_id',
        'decision_id',
        'type',
        'motif',
        'auteur_compte_id',
        'origine_systeme',
        'correlation',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(VerificationDossier::class, 'dossier_id', 'id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'auteur_compte_id', 'id');
    }
}
