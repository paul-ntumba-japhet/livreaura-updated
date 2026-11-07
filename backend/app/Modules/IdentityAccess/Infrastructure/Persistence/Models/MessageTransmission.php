<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTransmission extends Model
{
    protected $table = 'transmissions_messages';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $hidden = [
        'destination_chiffree',
        'contenu_chiffre_temporaire',
    ];

    protected $fillable = [
        'id',
        'message_id',
        'destinataire_cle',
        'canal',
        'compte_destinataire_id',
        'etat',
        'config_delais_id',
        'nombre_tentatives',
        'niveau_acquittement',
        'cle_chiffrement_ref',
    ];

    protected function casts(): array
    {
        return [
            'prochaine_tentative_a' => 'datetime',
            'nombre_tentatives' => 'integer',
            'bail_jusqua' => 'datetime',
            'envoye_a' => 'datetime',
            'acquitte_a' => 'datetime',
            'contenu_expire_a' => 'datetime',
            'contenu_purge_a' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(OutgoingMessage::class, 'message_id', 'id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TransmissionAttempt::class, 'transmission_id', 'id');
    }
}
