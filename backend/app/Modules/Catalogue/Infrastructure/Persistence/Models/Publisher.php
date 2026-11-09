<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publisher extends CatalogueRecord
{
    protected $table = 'editeur';

    protected $primaryKey = 'editeur_id';

    protected $fillable = [
        'editeur_id',
        'slug',
        'nom',
        'type',
        'presentation',
        'pays',
        'is_partner',
        'is_academic',
        'is_youth',
        'is_african',
        'is_indie',
        'mis_en_avant',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'is_partner' => 'boolean',
            'is_academic' => 'boolean',
            'is_youth' => 'boolean',
            'is_african' => 'boolean',
            'is_indie' => 'boolean',
            'mis_en_avant' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class, 'editeur_id', 'editeur_id');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(CatalogueCollection::class, 'editeur_id', 'editeur_id');
    }
}
