<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationMembershipPermission extends Model
{
    protected $table = 'adhesions_permissions';

    protected $primaryKey = 'adhesion_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'adhesion_id',
        'permission_id',
    ];
}
