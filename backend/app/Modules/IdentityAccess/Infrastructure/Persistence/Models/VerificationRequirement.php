<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationRequirement extends Model
{
    protected $table = 'exigences_verification';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'code',
        'version_regles',
        'type_sujet',
        'type_organisation_id',
        'pays',
        'niveau',
        'volet',
        'nature_preuve',
        'obligatoire',
        'autorise_non_applicable',
    ];

    protected function casts(): array
    {
        return [
            'obligatoire' => 'boolean',
            'autorise_non_applicable' => 'boolean',
            'date_effet' => 'datetime',
        ];
    }

    public function organizationType(): BelongsTo
    {
        return $this->belongsTo(OrganizationType::class, 'type_organisation_id', 'id');
    }

    public function requiresDocument(): bool
    {
        return in_array($this->volet, ['identite', 'mandat'], true);
    }

    public function requiresFiscalProfile(): bool
    {
        return $this->volet === 'fiscal';
    }

    public function requiresPayoutAccount(): bool
    {
        return $this->volet === 'reversement';
    }
}
