<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogueCollection extends CatalogueRecord
{
    protected $table = 'collection';

    protected $primaryKey = 'collection_id';

    protected $fillable = [
        'collection_id',
        'editeur_id',
        'slug',
        'nom',
        'kind',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class, 'editeur_id', 'editeur_id');
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class, 'collection_id', 'collection_id');
    }
}
