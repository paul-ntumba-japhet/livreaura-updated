<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeasureScope extends CatalogueRecord
{
    protected $table = 'portees_decisions_mesures';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'decision_id',
        'mesure_id',
        'resolution_id',
        'effet',
    ];

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(TargetResolution::class, 'resolution_id', 'id');
    }

    public function developments(): HasMany
    {
        return $this->hasMany(ScopeDevelopment::class, 'portee_id', 'id');
    }
}
