<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalProfile extends Model
{
    protected $table = 'profils_fiscaux';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'sujet_id',
        'version',
        'identifiant_fiscal',
        'pays_fiscal',
        'nom_legal',
        'statut_controle',
        'reference_privee',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(KycSubject::class, 'sujet_id', 'id');
    }
}
