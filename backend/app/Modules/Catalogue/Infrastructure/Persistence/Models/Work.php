<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\CatalogAuthor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Work extends CatalogueRecord
{
    protected $table = 'oeuvre';

    protected $primaryKey = 'oeuvre_id';

    protected $fillable = [
        'oeuvre_id',
        'slug',
        'titre',
        'sous_titre',
        'resume',
        'pages',
        'langue_principale_id',
        'statut',
        'availability',
        'badge',
        'africain',
        'mis_en_avant',
        'publie_a',
        'note_moyenne',
        'avis_count',
        'retire_a',
        'niveau_educatif_id',
        'collection_id',
    ];

    protected function casts(): array
    {
        return [
            'pages' => 'integer',
            'africain' => 'boolean',
            'mis_en_avant' => 'boolean',
            'publie_a' => 'datetime',
            'note_moyenne' => 'decimal:8',
            'avis_count' => 'integer',
            'retire_a' => 'datetime',
        ];
    }

    public function primaryLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'langue_principale_id', 'langue_id');
    }

    public function educationalLevel(): BelongsTo
    {
        return $this->belongsTo(EducationalLevel::class, 'niveau_educatif_id', 'niveau_educatif_id');
    }

    public function catalogueCollection(): BelongsTo
    {
        return $this->belongsTo(CatalogueCollection::class, 'collection_id', 'collection_id');
    }

    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class, 'oeuvre_id', 'oeuvre_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(WorkAuthor::class, 'oeuvre_id', 'oeuvre_id');
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogAuthor::class,
            'oeuvre_auteur',
            'oeuvre_id',
            'auteur_id',
            'oeuvre_id',
            'auteur_id',
        )->withPivot(['ordre_affichage', 'role']);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'oeuvre_categorie',
            'oeuvre_id',
            'categorie_id',
            'oeuvre_id',
            'categorie_id',
        );
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(
            Language::class,
            'oeuvre_langue',
            'oeuvre_id',
            'langue_id',
            'oeuvre_id',
            'langue_id',
        );
    }

    public function media(): HasMany
    {
        return $this->hasMany(CatalogueMedia::class, 'oeuvre_id', 'oeuvre_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'oeuvre_id', 'oeuvre_id');
    }

    public function selectionEntries(): HasMany
    {
        return $this->hasMany(SelectionWork::class, 'oeuvre_id', 'oeuvre_id');
    }
}
