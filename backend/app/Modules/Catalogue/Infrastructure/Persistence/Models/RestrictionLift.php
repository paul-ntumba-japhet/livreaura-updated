<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestrictionLift extends CatalogueRecord
{
    protected $table = 'levees_restrictions';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'restriction_id',
        'ligne_levee_id',
        'instruction_levee_id',
        'reference',
        'decide_a',
        'effet_a',
    ];

    protected function casts(): array
    {
        return [
            'decide_a' => 'datetime',
            'effet_a' => 'datetime',
        ];
    }

    public function restriction(): BelongsTo
    {
        return $this->belongsTo(ComplianceRestriction::class, 'restriction_id', 'id');
    }

    public function liftingLine(): BelongsTo
    {
        return $this->belongsTo(WithdrawalOrderLine::class, 'ligne_levee_id', 'id');
    }

    public function liftingInstruction(): BelongsTo
    {
        return $this->belongsTo(PublicationWithdrawalInstruction::class, 'instruction_levee_id', 'id');
    }
}
