<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationType extends Model
{
    protected $table = 'types_organisation';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'code',
        'libelle',
    ];

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'type_organisation_id', 'id');
    }
}
