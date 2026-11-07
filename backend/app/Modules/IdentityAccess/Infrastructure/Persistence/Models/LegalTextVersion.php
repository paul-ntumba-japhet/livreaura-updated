<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalTextVersion extends Model
{
    protected $table = 'textes_legaux_versions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'type',
        'version',
        'langue',
        'contenu',
        'reference_immuable',
        'empreinte',
        'requis_pour_activation',
    ];

    protected function casts(): array
    {
        return [
            'publie_a' => 'datetime',
            'requis_pour_activation' => 'boolean',
        ];
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class, 'texte_version_id', 'id');
    }
}
