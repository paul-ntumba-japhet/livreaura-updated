<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransmissionAttempt extends Model
{
    protected $table = 'tentatives_transmission';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'transmission_id',
        'numero',
        'resultat',
        'reference_prestataire',
        'code_erreur_masque',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'commence_a' => 'datetime',
            'termine_a' => 'datetime',
        ];
    }

    public function transmission(): BelongsTo
    {
        return $this->belongsTo(MessageTransmission::class, 'transmission_id', 'id');
    }
}
