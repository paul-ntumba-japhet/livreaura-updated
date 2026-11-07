<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use Database\Factories\IdentityAccess\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Account extends Authenticatable
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected $table = 'comptes';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $rememberTokenName = '';

    protected $hidden = [
        'hash_mdp',
    ];

    protected $fillable = [
        'id',
        'email_normalise',
        'telephone_normalise',
        'locale',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'statut' => AccountStatus::class,
            'email_verifie_a' => 'datetime',
            'telephone_verifie_a' => 'datetime',
            'cree_a' => 'datetime',
            'ferme_a' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->hash_mdp;
    }

    public function getAuthPasswordName(): string
    {
        return 'hash_mdp';
    }

    public function profile(): HasOne
    {
        return $this->hasOne(PersonalProfile::class, 'compte_id', 'id');
    }

    public function authenticationSessions(): HasMany
    {
        return $this->hasMany(AuthenticationSession::class, 'compte_id', 'id');
    }

    public function accountRoles(): HasMany
    {
        return $this->hasMany(AccountRole::class, 'compte_id', 'id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'compte_id', 'id');
    }

    public function internalAttributions(): HasMany
    {
        return $this->hasMany(InternalAttribution::class, 'compte_id', 'id');
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class, 'compte_id', 'id');
    }

    public function verificationChallenges(): HasMany
    {
        return $this->hasMany(VerificationChallenge::class, 'compte_id', 'id');
    }

    public function accountEvents(): HasMany
    {
        return $this->hasMany(AccountEvent::class, 'compte_sujet_id', 'id');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class, 'compte_id', 'id');
    }

    protected static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }
}
