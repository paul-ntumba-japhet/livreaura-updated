<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\Catalogue\Infrastructure\Persistence\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkLanguage extends CatalogueRecord
{
    use HasCompositePrimaryKey;

    protected $table = 'oeuvre_langue';

    protected $fillable = [
        'oeuvre_id',
        'langue_id',
    ];

    public function compositePrimaryKey(): array
    {
        return ['oeuvre_id', 'langue_id'];
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'oeuvre_id', 'oeuvre_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'langue_id', 'langue_id');
    }
}
