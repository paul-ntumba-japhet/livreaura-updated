<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutAccount extends Model
{
    protected $table = 'comptes_reversement';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'sujet_id',
        'version',
        'canal',
        'prestataire',
        'titulaire',
        'pays',
        'devise',
        'reference_prestataire',
        'affichage_masque',
        'statut_controle',
    ];

    protected $hidden = [
        'coordonnees_chiffrees',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'verifie_a' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(KycSubject::class, 'sujet_id', 'id');
    }
}
