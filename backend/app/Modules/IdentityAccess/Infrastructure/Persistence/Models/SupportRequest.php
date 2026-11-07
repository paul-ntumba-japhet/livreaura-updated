<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupportRequest extends Model
{
    protected $table = 'demandes_support';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'reference',
        'type',
        'demandeur_compte_id',
        'compte_concerne_id',
        'organisation_id',
        'reference_auteur_externe',
        'identite_etat',
        'conclusion',
    ];

    protected function casts(): array
    {
        return [
            'cree_a' => 'datetime',
            'identite_verifiee_a' => 'datetime',
            'clos_a' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'demandeur_compte_id', 'id');
    }

    public function concerned(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_concerne_id', 'id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organisation_id', 'id');
    }

    public function serviceTask(): HasOne
    {
        return $this->hasOne(ServiceTask::class, 'demande_support_id', 'id');
    }
}
