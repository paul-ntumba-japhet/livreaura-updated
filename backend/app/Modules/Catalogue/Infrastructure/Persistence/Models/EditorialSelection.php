<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EditorialSelection extends CatalogueRecord
{
    protected $table = 'selection_editoriale';

    protected $primaryKey = 'selection_id';

    protected $fillable = [
        'selection_id',
        'code',
        'libelle',
        'regle_ou_manuelle',
        'parametres',
        'actif',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'parametres' => 'array',
            'actif' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(SelectionWork::class, 'selection_id', 'selection_id');
    }

    public function works(): BelongsToMany
    {
        return $this->belongsToMany(
            Work::class,
            'selection_oeuvre',
            'selection_id',
            'oeuvre_id',
            'selection_id',
            'oeuvre_id',
        )->withPivot(['sort_order', 'ajoute_a']);
    }
}
