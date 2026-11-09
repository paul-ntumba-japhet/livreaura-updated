<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\Catalogue\Infrastructure\Persistence\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScopeDevelopment extends CatalogueRecord
{
    use HasCompositePrimaryKey;

    protected $table = 'developpements_portee';

    protected $fillable = [
        'portee_id',
        'resolution_enfant_id',
        'developpe_a',
        'preuve_perimetre',
    ];

    protected function casts(): array
    {
        return [
            'developpe_a' => 'datetime',
            'preuve_perimetre' => 'array',
        ];
    }

    public function compositePrimaryKey(): array
    {
        return ['portee_id', 'resolution_enfant_id'];
    }

    public function scope(): BelongsTo
    {
        return $this->belongsTo(MeasureScope::class, 'portee_id', 'id');
    }

    public function childResolution(): BelongsTo
    {
        return $this->belongsTo(TargetResolution::class, 'resolution_enfant_id', 'id');
    }
}
