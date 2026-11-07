<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingMessage extends Model
{
    protected $table = 'messages_sortants';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'cle_idempotence',
        'type',
        'operation',
        'correlation',
        'compte_id',
        'dossier_id',
        'demande_id',
        'defi_id',
        'evenement_compte_id',
        'evenement_kyc_id',
        'version_objet',
        'producteur',
        'schema_message_version',
        'empreinte_intention',
        'donnees_minimales',
    ];

    protected function casts(): array
    {
        return [
            'schema_message_version' => 'integer',
            'donnees_minimales' => 'array',
            'cree_a' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_id', 'id');
    }

    public function transmissions(): HasMany
    {
        return $this->hasMany(MessageTransmission::class, 'message_id', 'id');
    }
}
