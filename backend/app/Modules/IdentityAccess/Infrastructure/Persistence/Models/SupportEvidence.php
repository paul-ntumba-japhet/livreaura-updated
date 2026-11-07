<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportEvidence extends Model
{
    protected $table = 'pieces_support';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'demande_id',
        'echange_id',
        'deposant_id',
        'type',
        'reference_privee',
        'empreinte',
        'acces',
    ];

    protected function casts(): array
    {
        return [
            'depose_a' => 'datetime',
        ];
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class, 'demande_id', 'id');
    }
}
