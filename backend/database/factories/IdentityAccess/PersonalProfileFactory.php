<?php

namespace Database\Factories\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\PersonalProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalProfile>
 */
class PersonalProfileFactory extends Factory
{
    protected $model = PersonalProfile::class;

    public function definition(): array
    {
        return [
            'compte_id' => Account::factory(),
            'prenom' => fake()->firstName(),
            'nom' => fake()->lastName(),
            'nom_plume' => null,
            'pays' => 'CD',
            'ville' => 'Kinshasa',
            'langue_ecriture' => 'fr',
        ];
    }
}
