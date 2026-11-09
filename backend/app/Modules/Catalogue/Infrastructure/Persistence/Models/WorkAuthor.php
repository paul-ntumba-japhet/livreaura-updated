<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\Catalogue\Infrastructure\Persistence\Concerns\HasCompositePrimaryKey;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\CatalogAuthor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkAuthor extends CatalogueRecord
{
    use HasCompositePrimaryKey;

    protected $table = 'oeuvre_auteur';

    protected $fillable = [
        'oeuvre_id',
        'auteur_id',
        'ordre_affichage',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'ordre_affichage' => 'integer',
        ];
    }

    public function compositePrimaryKey(): array
    {
        return ['oeuvre_id', 'auteur_id'];
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'oeuvre_id', 'oeuvre_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(CatalogAuthor::class, 'auteur_id', 'auteur_id');
    }
}
