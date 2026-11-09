<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EffectConfirmation extends CatalogueRecord
{
    protected $table = 'confirmations_effet';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'ligne_id',
        'autorite',
        'reference_demande',
        'statut',
        'recu_instruction_a',
        'applique_a',
        'resultat',
        'revision_source',
    ];

    protected function casts(): array
    {
        return [
            'recu_instruction_a' => 'datetime',
            'applique_a' => 'datetime',
            'resultat' => 'array',
        ];
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(WithdrawalOrderLine::class, 'ligne_id', 'id');
    }
}
