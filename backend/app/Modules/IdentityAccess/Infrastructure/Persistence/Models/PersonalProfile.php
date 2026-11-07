<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence\Models;

use Database\Factories\IdentityAccess\PersonalProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalProfile extends Model
{
    /** @use HasFactory<PersonalProfileFactory> */
    use HasFactory;

    protected $table = 'profils_particuliers';

    protected $primaryKey = 'compte_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'compte_id',
        'prenom',
        'nom',
        'nom_plume',
        'pays',
        'ville',
        'langue_ecriture',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'compte_id', 'id');
    }

    protected static function newFactory(): PersonalProfileFactory
    {
        return PersonalProfileFactory::new();
    }
}
