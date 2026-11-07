<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consent extends Model
{
    protected $table = 'consentements';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'compte_id',
        'texte_version_id',
        'nature',
        'source',
        'canal',
    ];

    protected function casts(): array
    {
        return [
            'accepte_a' => 'datetime',
            'retire_a' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_id', 'id');
    }

    public function legalText(): BelongsTo
    {
        return $this->belongsTo(LegalTextVersion::class, 'texte_version_id', 'id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ConsentEvent::class, 'consentement_id', 'id');
    }
}
