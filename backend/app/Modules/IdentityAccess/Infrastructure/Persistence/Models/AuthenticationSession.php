<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use Database\Factories\IdentityAccess\AuthenticationSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthenticationSession extends Model
{
    /** @use HasFactory<AuthenticationSessionFactory> */
    use HasFactory;

    protected $table = 'sessions_authentification';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'compte_id',
        'empreinte_reference',
        'portee',
        'statut',
        'ouverte_a',
        'derniere_activite_a',
        'expire_absolue_a',
        'expire_inactivite_a',
        'revoque_a',
        'motif_revocation',
        'contexte_client',
        'demande_support_id',
        'autorise_par',
        'config_acces_id',
    ];

    protected function casts(): array
    {
        return [
            'portee' => SessionAccessScope::class,
            'statut' => SessionStatus::class,
            'ouverte_a' => 'datetime',
            'derniere_activite_a' => 'datetime',
            'expire_absolue_a' => 'datetime',
            'expire_inactivite_a' => 'datetime',
            'revoque_a' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_id', 'id');
    }

    protected static function newFactory(): AuthenticationSessionFactory
    {
        return AuthenticationSessionFactory::new();
    }
}
