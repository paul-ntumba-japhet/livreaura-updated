<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\CatalogAuthor;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuthorTag extends CatalogueRecord
{
    protected $table = 'tag_auteur';

    protected $primaryKey = 'tag_auteur_id';

    protected $fillable = [
        'tag_auteur_id',
        'slug',
        'nom',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(AuthorTagAssignment::class, 'tag_auteur_id', 'tag_auteur_id');
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogAuthor::class,
            'auteur_tag',
            'tag_auteur_id',
            'auteur_id',
            'tag_auteur_id',
            'auteur_id',
        );
    }
}
