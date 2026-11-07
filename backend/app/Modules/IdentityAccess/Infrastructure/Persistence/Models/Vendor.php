<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    protected $table = 'vendeurs';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'sujet_id',
        'profil_fiscal_id',
        'compte_reversement_id',
        'serie_facturation',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(KycSubject::class, 'sujet_id', 'id');
    }

    public function fiscalProfile(): BelongsTo
    {
        return $this->belongsTo(FiscalProfile::class, 'profil_fiscal_id', 'id');
    }

    public function payoutAccount(): BelongsTo
    {
        return $this->belongsTo(PayoutAccount::class, 'compte_reversement_id', 'id');
    }
}
