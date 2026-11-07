<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceConfiguration extends Model
{
    protected $table = 'configurations_service';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'code',
        'version',
        'schema_version',
        'valeurs',
        'empreinte',
        'cree_par',
        'approuve_par',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'schema_version' => 'integer',
            'valeurs' => 'array',
            'cree_a' => 'datetime',
            'approuve_a' => 'datetime',
            'effet_a' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cree_par', 'id');
    }
}
