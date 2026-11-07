<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransmissionSecret extends Model
{
    protected $table = 'secrets_transmission';

    protected $primaryKey = 'transmission_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'transmission_id',
        'defi_id',
        'acces_reponse_id',
    ];

    public function transmission(): BelongsTo
    {
        return $this->belongsTo(MessageTransmission::class, 'transmission_id', 'id');
    }
}
