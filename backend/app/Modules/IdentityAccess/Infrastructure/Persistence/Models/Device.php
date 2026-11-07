<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $table = 'appareils';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $hidden = [
        'empreinte_secret_enregistrement',
        'fingerprint_hash',
    ];

    protected $fillable = [
        'id',
        'compte_id',
        'identifiant_enregistrement',
        'libelle',
        'plateforme',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'enregistre_a' => 'datetime',
            'dernier_vu_a' => 'datetime',
            'revoque_a' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_id', 'id');
    }
}
