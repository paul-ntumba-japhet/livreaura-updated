<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetResolution extends CatalogueRecord
{
    protected $table = 'resolutions_cibles';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'contenu_vise_id',
        'numero',
        'resultat',
        'oeuvre_id',
        'edition_id',
        'offre_id',
        'media_catalogue_id',
        'avis_id',
        'version_contenu_id',
        'preuve_resolution',
        'resolu_a',
        'auteur_compte_id',
        'origine_systeme',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'preuve_resolution' => 'array',
            'resolu_a' => 'datetime',
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

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'offre_id', 'offre_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(CatalogueMedia::class, 'media_catalogue_id', 'media_catalogue_id');
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'avis_id', 'avis_id');
    }

    public function authorAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'auteur_compte_id', 'id');
    }
}
