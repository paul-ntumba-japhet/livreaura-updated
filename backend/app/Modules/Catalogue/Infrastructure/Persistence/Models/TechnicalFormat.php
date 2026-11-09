<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicalFormat extends CatalogueRecord
{
    protected $table = 'format_technique';

    protected $primaryKey = 'format_id';

    protected $fillable = [
        'format_id',
        'code',
        'libelle',
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

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'format_id', 'format_id');
    }
}
