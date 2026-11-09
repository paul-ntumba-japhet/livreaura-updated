<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplianceRestriction extends CatalogueRecord
{
    protected $table = 'restrictions_conformite';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'ligne_creatrice_id',
        'instruction_publication_id',
        'cause',
        'debut',
        'fin',
        'levee_decidee_a',
    ];

    protected function casts(): array
    {
        return [
            'debut' => 'datetime',
            'fin' => 'datetime',
            'levee_decidee_a' => 'datetime',
        ];
    }

    public function creatingLine(): BelongsTo
    {
        return $this->belongsTo(WithdrawalOrderLine::class, 'ligne_creatrice_id', 'id');
    }

    public function publicationInstruction(): BelongsTo
    {
        return $this->belongsTo(PublicationWithdrawalInstruction::class, 'instruction_publication_id', 'id');
    }

    public function lifts(): HasMany
    {
        return $this->hasMany(RestrictionLift::class, 'restriction_id', 'id');
    }
}
