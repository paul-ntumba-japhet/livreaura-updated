<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTaskEvent extends Model
{
    protected $table = 'evenements_taches';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'tache_id',
        'type',
        'motif',
        'origine_systeme',
        'correlation',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'prochaine_action_a' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ServiceTask::class, 'tache_id', 'id');
    }
}
