<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogAuthor extends Model
{
    protected $table = 'auteur';

    protected $primaryKey = 'auteur_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'auteur_id',
        'compte_id',
        'slug',
        'nom_affiche',
        'bio',
        'pays',
        'role_affiche',
        'verified',
        'mis_en_avant',
        'actif',
        'statut_publication',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'integer',
            'mis_en_avant' => 'integer',
            'actif' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_id', 'id');
    }
}
