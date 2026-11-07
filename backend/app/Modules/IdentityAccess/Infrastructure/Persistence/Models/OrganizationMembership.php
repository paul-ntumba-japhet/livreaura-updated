<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrganizationMembership extends Model
{
    protected $table = 'adhesions_organisation';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'compte_id',
        'organisation_id',
        'fonction',
        'principal',
        'statut',
        'origine',
        'motif',
    ];

    protected function casts(): array
    {
        return [
            'principal' => 'boolean',
            'cree_a' => 'datetime',
            'active_a' => 'datetime',
            'revoque_a' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_id', 'id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organisation_id', 'id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            OrganizationPermission::class,
            'adhesions_permissions',
            'adhesion_id',
            'permission_id',
        );
    }
}
