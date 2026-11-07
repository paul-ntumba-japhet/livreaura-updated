<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalPermission extends Model
{
    protected $table = 'permissions_internes';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'code',
        'libelle',
    ];

    public function attributions(): HasMany
    {
        return $this->hasMany(InternalAttribution::class, 'permission_id', 'id');
    }
}
