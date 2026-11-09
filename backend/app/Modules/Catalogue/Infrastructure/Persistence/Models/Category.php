<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends CatalogueRecord
{
    protected $table = 'categorie';

    protected $primaryKey = 'categorie_id';

    protected $fillable = [
        'categorie_id',
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
        return $this->belongsTo(self::class, 'parent_id', 'categorie_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'categorie_id');
    }

    public function works(): BelongsToMany
    {
        return $this->belongsToMany(
            Work::class,
            'oeuvre_categorie',
            'categorie_id',
            'oeuvre_id',
            'categorie_id',
            'oeuvre_id',
        );
    }
}
