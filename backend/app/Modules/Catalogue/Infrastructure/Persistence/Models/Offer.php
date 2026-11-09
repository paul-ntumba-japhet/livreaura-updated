<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends CatalogueRecord
{
    protected $table = 'offre';

    protected $primaryKey = 'offre_id';

    protected $fillable = [
        'offre_id',
        'edition_id',
        'format_id',
        'vendeur_id',
        'modalite_commerciale',
        'prix',
        'devise',
        'condition',
        'sku',
        'actif',
        'stock',
        'retire_a',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:4',
            'actif' => 'boolean',
            'stock' => 'integer',
            'retire_a' => 'datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class, 'edition_id', 'edition_id');
    }

    public function format(): BelongsTo
    {
        return $this->belongsTo(TechnicalFormat::class, 'format_id', 'format_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendeur_id', 'id');
    }
}
