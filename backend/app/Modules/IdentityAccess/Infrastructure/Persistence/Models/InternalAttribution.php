<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalAttribution extends Model
{
    protected $table = 'attributions_internes';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'compte_id',
        'permission_id',
        'attribue_par',
        'motif',
    ];

    protected function casts(): array
    {
        return [
            'attribue_a' => 'datetime',
            'expire_a' => 'datetime',
            'revoque_a' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_id', 'id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(InternalPermission::class, 'permission_id', 'id');
    }
}
