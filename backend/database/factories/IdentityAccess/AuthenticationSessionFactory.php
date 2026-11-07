<?php

namespace Database\Factories\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuthenticationSession>
 */
class AuthenticationSessionFactory extends Factory
{
    protected $model = AuthenticationSession::class;

    public function definition(): array
    {
        $openedAt = now();

        return [
            'id' => strtoupper((string) Str::ulid()),
            'compte_id' => Account::factory()->active(),
            'empreinte_reference' => hash('sha256', (string) Str::ulid()),
            'portee' => SessionAccessScope::Normal,
            'statut' => SessionStatus::Active,
            'ouverte_a' => $openedAt,
            'derniere_activite_a' => $openedAt,
            'expire_absolue_a' => $openedAt->copy()->addHours(12),
            'expire_inactivite_a' => $openedAt->copy()->addMinutes(30),
            'revoque_a' => null,
            'motif_revocation' => null,
            'contexte_client' => null,
            'demande_support_id' => null,
            'autorise_par' => null,
            'config_acces_id' => strtoupper((string) Str::ulid()),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'statut' => SessionStatus::Expired,
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => [
            'statut' => SessionStatus::Revoked,
            'revoque_a' => now(),
            'motif_revocation' => 'test',
        ]);
    }

    public function limited(): static
    {
        return $this->state(fn (): array => [
            'portee' => SessionAccessScope::Limited,
            'demande_support_id' => null,
            'autorise_par' => null,
        ]);
    }
}
