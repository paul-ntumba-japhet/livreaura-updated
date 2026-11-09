<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edition extends CatalogueRecord
{
    protected $table = 'edition';

    protected $primaryKey = 'edition_id';

    protected $fillable = [
        'edition_id',
        'oeuvre_id',
        'editeur_id',
        'isbn',
        'titre_edition',
        'editeur_legal_libelle',
        'role_ae',
        'responsable_depot',
        'territoire',
        'date_edition',
        'statut_depot',
        'statut',
        'consultable',
    ];

    protected function casts(): array
    {
        return [
            'date_edition' => 'date',
            'consultable' => 'boolean',
        ];
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'oeuvre_id', 'oeuvre_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class, 'editeur_id', 'editeur_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'edition_id', 'edition_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(CatalogueMedia::class, 'edition_id', 'edition_id');
    }
}
