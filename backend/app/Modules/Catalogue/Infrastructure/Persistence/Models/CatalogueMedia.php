<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogueMedia extends CatalogueRecord
{
    protected $table = 'media_catalogue';

    protected $primaryKey = 'media_catalogue_id';

    protected $fillable = [
        'media_catalogue_id',
        'oeuvre_id',
        'edition_id',
        'type',
        'reference_publique',
        'texte_alternatif',
        'ordre',
        'actif',
        'retire_a',
        'volume_apercu',
        'unite_volume',
    ];

    protected function casts(): array
    {
        return [
            'ordre' => 'integer',
            'actif' => 'boolean',
            'retire_a' => 'datetime',
            'volume_apercu' => 'decimal:8',
        ];
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'oeuvre_id', 'oeuvre_id');
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class, 'edition_id', 'edition_id');
    }
}
