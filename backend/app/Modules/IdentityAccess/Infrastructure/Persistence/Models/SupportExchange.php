<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportExchange extends Model
{
    protected $table = 'echanges_support';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'demande_id',
        'auteur_compte_id',
        'origine',
        'visibilite',
        'contenu_prive',
        'canal',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class, 'demande_id', 'id');
    }
}
