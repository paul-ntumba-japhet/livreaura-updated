<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends CatalogueRecord
{
    protected $table = 'langue';

    protected $primaryKey = 'langue_id';

    protected $fillable = [
        'langue_id',
        'code',
        'nom',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function primaryWorks(): HasMany
    {
        return $this->hasMany(Work::class, 'langue_principale_id', 'langue_id');
    }

    public function works(): BelongsToMany
    {
        return $this->belongsToMany(
            Work::class,
            'oeuvre_langue',
            'langue_id',
            'oeuvre_id',
            'langue_id',
            'oeuvre_id',
        );
    }
}
