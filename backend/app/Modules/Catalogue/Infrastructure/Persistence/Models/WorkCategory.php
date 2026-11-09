<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\Catalogue\Infrastructure\Persistence\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkCategory extends CatalogueRecord
{
    use HasCompositePrimaryKey;

    protected $table = 'oeuvre_categorie';

    protected $fillable = [
        'oeuvre_id',
        'categorie_id',
    ];

    public function compositePrimaryKey(): array
    {
        return ['oeuvre_id', 'categorie_id'];
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'oeuvre_id', 'oeuvre_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categorie_id', 'categorie_id');
    }
}
