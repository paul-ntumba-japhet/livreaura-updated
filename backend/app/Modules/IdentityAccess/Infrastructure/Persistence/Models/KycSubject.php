<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KycSubject extends Model
{
    protected $table = 'sujets_kyc';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'profil_compte_id',
        'organisation_id',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PersonalProfile::class, 'profil_compte_id', 'compte_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organisation_id', 'id');
    }

    public function dossiers(): HasMany
    {
        return $this->hasMany(VerificationDossier::class, 'sujet_id', 'id');
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(VerificationCapability::class, 'sujet_id', 'id');
    }

    public function isOrganization(): bool
    {
        return is_string($this->organisation_id) && $this->organisation_id !== '';
    }
}
