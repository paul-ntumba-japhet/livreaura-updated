<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountEvent extends Model
{
    protected $table = 'evenements_compte';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'compte_sujet_id',
        'adhesion_id',
        'compte_role_id',
        'permission_id',
        'attribution_interne_id',
        'type',
        'motif',
        'auteur_compte_id',
        'origine_systeme',
        'correlation',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'fin_prevue' => 'datetime',
        ];
    }

    public function subjectAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_sujet_id', 'id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'auteur_compte_id', 'id');
    }
}
