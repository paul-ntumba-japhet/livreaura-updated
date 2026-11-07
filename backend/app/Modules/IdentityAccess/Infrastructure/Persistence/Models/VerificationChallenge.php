<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use App\Modules\IdentityAccess\Domain\Enums\ChallengePurpose;
use Database\Factories\IdentityAccess\VerificationChallengeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationChallenge extends Model
{
    /** @use HasFactory<VerificationChallengeFactory> */
    use HasFactory;

    protected $table = 'defis_verification';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $hidden = [
        'destination_chiffree',
        'empreinte_destination',
        'empreinte_secret',
    ];

    protected $fillable = [
        'id',
        'compte_id',
        'finalite',
        'canal',
        'nombre_essais',
        'plafond_essais',
    ];

    protected function casts(): array
    {
        return [
            'finalite' => ChallengePurpose::class,
            'cree_a' => 'datetime',
            'expire_a' => 'datetime',
            'consomme_a' => 'datetime',
            'revoque_a' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_id', 'id');
    }

    protected static function newFactory(): VerificationChallengeFactory
    {
        return VerificationChallengeFactory::new();
    }
}
