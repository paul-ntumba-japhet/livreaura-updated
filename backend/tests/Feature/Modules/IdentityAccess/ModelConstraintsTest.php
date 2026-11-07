<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\PersonalProfile;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModelConstraintsTest extends TestCase
{
    public function test_pending_account_and_profile_can_be_persisted(): void
    {
        $account = Account::factory()->create();
        $profile = PersonalProfile::factory()->create(['compte_id' => $account->id]);

        $this->assertSame(AccountStatus::Pending, $account->fresh()->statut);
        $this->assertNull($account->fresh()->ferme_a);
        $this->assertSame($account->id, $profile->compte_id);
        $this->assertTrue($account->profile()->exists());
    }

    public function test_active_account_requires_verified_email(): void
    {
        $account = Account::factory()->active()->create();

        $this->assertSame(AccountStatus::Active, $account->statut);
        $this->assertNotNull($account->email_verifie_a);
    }

    public function test_closed_account_requires_ferme_a(): void
    {
        $account = Account::factory()->closed()->create();

        $this->assertSame(AccountStatus::Closed, $account->statut);
        $this->assertNotNull($account->ferme_a);
    }

    public function test_email_normalise_is_unique(): void
    {
        $email = 'unique.'.strtolower((string) Str::ulid()).'@example.test';
        Account::factory()->create(['email_normalise' => $email]);

        $this->expectException(QueryException::class);

        Account::factory()->create(['email_normalise' => $email]);
    }

    public function test_account_status_rejects_unknown_value(): void
    {
        $this->expectException(QueryException::class);

        DB::table('comptes')->insert([
            'id' => strtoupper((string) Str::ulid()),
            'email_normalise' => 'deleted.'.strtolower((string) Str::ulid()).'@example.test',
            'hash_mdp' => Account::factory()->make()->getAuthPassword(),
            'statut' => 'deleted',
            'locale' => 'fr',
            'cree_a' => now(),
        ]);
    }

    public function test_active_account_without_email_verification_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        Account::factory()->create([
            'statut' => AccountStatus::Active,
            'email_verifie_a' => null,
            'ferme_a' => null,
        ]);
    }

    public function test_closed_account_without_ferme_a_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        Account::factory()->create([
            'statut' => AccountStatus::Closed,
            'email_verifie_a' => now(),
            'ferme_a' => null,
        ]);
    }

    public function test_pending_account_cannot_have_ferme_a(): void
    {
        $this->expectException(QueryException::class);

        Account::factory()->create([
            'statut' => AccountStatus::Pending,
            'ferme_a' => now(),
        ]);
    }

    public function test_invalid_ulid_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        Account::factory()->create(['id' => 'Z'.str_repeat('A', 25)]);
    }

    public function test_authentication_session_can_be_persisted(): void
    {
        $session = AuthenticationSession::factory()->create();

        $this->assertSame(SessionStatus::Active, $session->statut);
        $this->assertSame(SessionAccessScope::Normal, $session->portee);
        $this->assertTrue($session->account()->exists());
    }

    public function test_session_status_rejects_unknown_value(): void
    {
        $account = Account::factory()->active()->create();
        $openedAt = now();

        $this->expectException(QueryException::class);

        DB::table('sessions_authentification')->insert([
            'id' => strtoupper((string) Str::ulid()),
            'compte_id' => $account->id,
            'empreinte_reference' => hash('sha256', (string) Str::ulid()),
            'portee' => 'normal',
            'statut' => 'idle',
            'ouverte_a' => $openedAt,
            'derniere_activite_a' => $openedAt,
            'expire_absolue_a' => $openedAt->copy()->addHours(12),
            'expire_inactivite_a' => $openedAt->copy()->addMinutes(30),
            'config_acces_id' => strtoupper((string) Str::ulid()),
        ]);
    }

    public function test_support_only_session_without_support_fields_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        AuthenticationSession::factory()->create([
            'portee' => SessionAccessScope::SupportOnly,
            'demande_support_id' => null,
            'autorise_par' => null,
        ]);
    }

    public function test_normal_session_cannot_carry_support_fields(): void
    {
        $this->expectException(QueryException::class);

        AuthenticationSession::factory()->create([
            'portee' => SessionAccessScope::Normal,
            'demande_support_id' => strtoupper((string) Str::ulid()),
            'autorise_par' => strtoupper((string) Str::ulid()),
        ]);
    }

    public function test_verification_challenge_can_be_persisted(): void
    {
        $challenge = VerificationChallenge::factory()->create();

        $this->assertSame('email_activation', $challenge->finalite->value);
        $this->assertSame(0, $challenge->nombre_essais);
        $this->assertTrue($challenge->account()->exists());
    }

    public function test_challenge_cannot_be_both_consumed_and_revoked(): void
    {
        $this->expectException(QueryException::class);

        VerificationChallenge::factory()->create([
            'consomme_a' => now(),
            'revoque_a' => now(),
        ]);
    }

    public function test_challenge_purpose_rejects_unknown_value(): void
    {
        $account = Account::factory()->create();
        $createdAt = now();

        $this->expectException(QueryException::class);

        DB::table('defis_verification')->insert([
            'id' => strtoupper((string) Str::ulid()),
            'compte_id' => $account->id,
            'finalite' => 'sms_otp',
            'canal' => 'email',
            'destination_chiffree' => 'ciphertext',
            'empreinte_destination' => hash('sha256', 'destination'),
            'destination_masquee' => 'a***@example.test',
            'empreinte_secret' => hash('sha256', 'secret'),
            'cree_a' => $createdAt,
            'expire_a' => $createdAt->copy()->addHour(),
            'nombre_essais' => 0,
            'plafond_essais' => 5,
        ]);
    }
}
