<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationDocument extends Model
{
    protected $table = 'documents_justificatifs';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'dossier_id',
        'deposant_id',
        'type_doc',
        'reference_privee',
        'empreinte',
        'numero',
        'autorite',
        'statut_verification',
        'motif_rejet',
    ];

    protected function casts(): array
    {
        return [
            'delivre_le' => 'date',
            'expire_le' => 'date',
            'depose_a' => 'datetime',
        ];
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(VerificationDossier::class, 'dossier_id', 'id');
    }
}
