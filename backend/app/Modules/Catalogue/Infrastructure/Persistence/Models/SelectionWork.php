<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\Catalogue\Infrastructure\Persistence\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelectionWork extends CatalogueRecord
{
    use HasCompositePrimaryKey;

    protected $table = 'selection_oeuvre';

    protected $fillable = [
        'selection_id',
        'oeuvre_id',
        'sort_order',
        'ajoute_a',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'ajoute_a' => 'datetime',
        ];
    }

    public function compositePrimaryKey(): array
    {
        return ['selection_id', 'oeuvre_id'];
    }

    public function selection(): BelongsTo
    {
        return $this->belongsTo(EditorialSelection::class, 'selection_id', 'selection_id');
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'oeuvre_id', 'oeuvre_id');
    }
}
