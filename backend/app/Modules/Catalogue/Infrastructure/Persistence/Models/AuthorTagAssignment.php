<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\Catalogue\Infrastructure\Persistence\Concerns\HasCompositePrimaryKey;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\CatalogAuthor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorTagAssignment extends CatalogueRecord
{
    use HasCompositePrimaryKey;

    protected $table = 'auteur_tag';

    protected $fillable = [
        'auteur_id',
        'tag_auteur_id',
    ];

    public function compositePrimaryKey(): array
    {
        return ['auteur_id', 'tag_auteur_id'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(CatalogAuthor::class, 'auteur_id', 'auteur_id');
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(AuthorTag::class, 'tag_auteur_id', 'tag_auteur_id');
    }
}
