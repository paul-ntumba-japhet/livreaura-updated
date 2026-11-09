<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationalLevel extends CatalogueRecord
{
    protected $table = 'niveau_educatif';

    protected $primaryKey = 'niveau_educatif_id';

    protected $fillable = [
        'niveau_educatif_id',
        'parent_id',
        'slug',
        'nom',
        'sort_order',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'niveau_educatif_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'niveau_educatif_id');
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class, 'niveau_educatif_id', 'niveau_educatif_id');
    }
}
