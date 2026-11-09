<?php

namespace App\Modules\Catalogue\Infrastructure\Persistence\Models;

use App\Modules\Catalogue\Infrastructure\Persistence\CatalogueRecord;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicationWithdrawalInstruction extends CatalogueRecord
{
    protected $table = 'instructions_retrait_publication';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'manuscrit_id',
        'producteur',
        'reference_instruction',
        'empreinte_intention',
        'effet',
        'restriction_source_id',
        'restriction_levee_id',
        'fin_effet_a',
        'cible_manuscrit_id',
        'oeuvre_id',
        'edition_id',
        'offre_id',
        'version_id',
        'motif',
        'reference_decision',
        'effet_a',
        'recu_a',
        'statut',
        'auteur_compte_id',
        'origine_systeme',
        'resultat',
    ];

    protected function casts(): array
    {
        return [
            'fin_effet_a' => 'datetime',
            'effet_a' => 'datetime',
            'recu_a' => 'datetime',
            'resultat' => 'array',
        ];
    }

    public function sourceRestriction(): BelongsTo
    {
        return $this->belongsTo(ComplianceRestriction::class, 'restriction_source_id', 'id');
    }

    public function linkedRestriction(): BelongsTo
    {
        return $this->belongsTo(ComplianceRestriction::class, 'restriction_levee_id', 'id');
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'oeuvre_id', 'oeuvre_id');
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class, 'edition_id', 'edition_id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'offre_id', 'offre_id');
    }

    public function authorAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'auteur_compte_id', 'id');
    }
}
