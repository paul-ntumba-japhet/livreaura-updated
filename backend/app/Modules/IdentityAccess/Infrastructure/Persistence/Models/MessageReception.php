<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class MessageReception extends Model
{
    protected $table = 'receptions_messages';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'evenement_externe_id',
        'producteur',
        'consommateur',
        'correlation',
        'empreinte_intention',
        'schema_message_version',
        'donnees_minimales',
        'niveau_acquittement',
        'reference_resultat',
    ];

    protected function casts(): array
    {
        return [
            'recu_a' => 'datetime',
            'traite_a' => 'datetime',
            'donnees_minimales' => 'array',
            'schema_message_version' => 'integer',
        ];
    }
}
