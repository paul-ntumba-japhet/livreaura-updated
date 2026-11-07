<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    protected $table = 'organisations';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'type_organisation_id',
        'raison_sociale',
        'email_pro',
        'site',
        'pays',
        'ville',
        'statut_operationnel',
    ];

    protected function casts(): array
    {
        return [
            'cree_a' => 'datetime',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(OrganizationType::class, 'type_organisation_id', 'id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class, 'organisation_id', 'id');
    }

    public function kycSubject(): HasOne
    {
        return $this->hasOne(KycSubject::class, 'organisation_id', 'id');
    }
}
