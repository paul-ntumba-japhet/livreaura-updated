<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTask extends Model
{
    protected $table = 'taches_service';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'demande_support_id',
        'dossier_kyc_id',
        'responsable_id',
        'etat_travail',
        'priorite',
        'calendrier_config_id',
        'delais_config_id',
    ];

    protected function casts(): array
    {
        return [
            'cree_a' => 'datetime',
            'affecte_a' => 'datetime',
            'premiere_prise_a' => 'datetime',
            'premiere_reponse_a' => 'datetime',
            'echeance_prise_a' => 'datetime',
            'echeance_reponse_a' => 'datetime',
            'prochaine_action_a' => 'datetime',
            'clos_a' => 'datetime',
        ];
    }

    public function supportRequest(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class, 'demande_support_id', 'id');
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(VerificationDossier::class, 'dossier_kyc_id', 'id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'responsable_id', 'id');
    }
}
