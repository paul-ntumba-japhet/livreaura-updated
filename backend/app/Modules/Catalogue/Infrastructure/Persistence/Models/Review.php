<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends CatalogueRecord
{
    protected $table = 'avis';

    protected $primaryKey = 'avis_id';

    protected $fillable = [
        'avis_id',
        'compte_id',
        'oeuvre_id',
        'note',
        'corps',
        'statut',
        'soumis_a',
        'moderateur_compte_id',
        'modere_a',
        'motif_rejet',
    ];

    protected function casts(): array
    {
        return [
            'note' => 'integer',
            'soumis_a' => 'datetime',
            'modere_a' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_id', 'id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'moderateur_compte_id', 'id');
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'oeuvre_id', 'oeuvre_id');
    }
}
