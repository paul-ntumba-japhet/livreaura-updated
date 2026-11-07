<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSubstitute extends Model
{
    protected $table = 'suppleances_service';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'titulaire_id',
        'suppleant_id',
        'fonction',
        'defini_par',
        'motif',
    ];

    protected function casts(): array
    {
        return [
            'debut_a' => 'datetime',
            'fin_a' => 'datetime',
            'defini_a' => 'datetime',
            'annule_a' => 'datetime',
        ];
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'titulaire_id', 'id');
    }

    public function substitute(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'suppleant_id', 'id');
    }

    public function definedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'defini_par', 'id');
    }
}
