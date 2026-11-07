<?php

namespace Database\Factories\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'id' => strtoupper((string) Str::ulid()),
            'email_normalise' => strtolower(fake()->unique()->safeEmail()),
            'telephone_normalise' => null,
            'hash_mdp' => static::$password ??= Hash::make('password'),
            'statut' => AccountStatus::Pending,
            'locale' => 'fr',
            'email_verifie_a' => null,
            'telephone_verifie_a' => null,
            'cree_a' => now(),
            'ferme_a' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'statut' => AccountStatus::Active,
            'email_verifie_a' => now(),
            'ferme_a' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => [
            'statut' => AccountStatus::Suspended,
            'email_verifie_a' => now(),
            'ferme_a' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'statut' => AccountStatus::Closed,
            'email_verifie_a' => now(),
            'ferme_a' => now(),
        ]);
    }
}
