<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportDocumentGrant extends Model
{
    protected $table = 'acces_documents_support';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'demande_support_id',
        'compte_id',
        'facture_vente_id',
        'facture_commission_id',
        'avoir_id',
        'recu_paiement_id',
        'autorise_par',
        'motif',
    ];

    protected function casts(): array
    {
        return [
            'debut' => 'datetime',
            'fin' => 'datetime',
            'revoque_a' => 'datetime',
        ];
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class, 'demande_support_id', 'id');
    }
}
