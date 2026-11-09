<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WithdrawalOrderLine extends CatalogueRecord
{
    protected $table = 'lignes_ordres_retrait';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'ordre_id',
        'resolution_id',
        'portee_id',
        'action',
        'reference_effet',
        'restriction_levee_id',
        'effet_a',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'effet_a' => 'datetime',
        ];
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(TargetResolution::class, 'resolution_id', 'id');
    }

    public function scope(): BelongsTo
    {
        return $this->belongsTo(MeasureScope::class, 'portee_id', 'id');
    }

    public function restrictionRecord(): BelongsTo
    {
        return $this->belongsTo(ComplianceRestriction::class, 'restriction_levee_id', 'id');
    }

    public function effectConfirmations(): HasMany
    {
        return $this->hasMany(EffectConfirmation::class, 'ligne_id', 'id');
    }
}
