<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VerificationDossier extends Model
{
    protected $table = 'dossiers_verification';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'sujet_id',
        'numero',
        'version',
        'precedent_id',
        'niveau',
        'statut',
        'deposant_id',
        'profil_fiscal_id',
        'compte_reversement_id',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'cree_a' => 'datetime',
            'soumis_a' => 'datetime',
            'clos_a' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(KycSubject::class, 'sujet_id', 'id');
    }

    public function depositor(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deposant_id', 'id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VerificationDocument::class, 'dossier_id', 'id');
    }

    public function controls(): HasMany
    {
        return $this->hasMany(VerificationControl::class, 'dossier_id', 'id');
    }

    public function fiscalProfile(): BelongsTo
    {
        return $this->belongsTo(FiscalProfile::class, 'profil_fiscal_id', 'id');
    }

    public function payoutAccount(): BelongsTo
    {
        return $this->belongsTo(PayoutAccount::class, 'compte_reversement_id', 'id');
    }

    public function serviceTask(): HasOne
    {
        return $this->hasOne(ServiceTask::class, 'dossier_kyc_id', 'id');
    }

    public function decision(): HasOne
    {
        return $this->hasOne(VerificationDecision::class, 'dossier_id', 'id');
    }

    public function isDraft(): bool
    {
        return $this->statut === 'draft';
    }

    public function isInProgress(): bool
    {
        return in_array($this->statut, ['draft', 'submitted', 'under_review'], true);
    }
}
