<?php

namespace Database\Factories\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\ChallengePurpose;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VerificationChallenge>
 */
class VerificationChallengeFactory extends Factory
{
    protected $model = VerificationChallenge::class;

    public function definition(): array
    {
        $createdAt = now();

        return [
            'id' => strtoupper((string) Str::ulid()),
            'compte_id' => Account::factory(),
            'finalite' => ChallengePurpose::EmailActivation,
            'canal' => 'email',
            'destination_chiffree' => 'ciphertext',
            'empreinte_destination' => hash('sha256', 'destination'),
            'destination_masquee' => 'a***@example.test',
            'empreinte_secret' => hash('sha256', 'secret'),
            'cree_a' => $createdAt,
            'expire_a' => $createdAt->copy()->addHour(),
            'consomme_a' => null,
            'revoque_a' => null,
            'nombre_essais' => 0,
            'plafond_essais' => 5,
        ];
    }
}
