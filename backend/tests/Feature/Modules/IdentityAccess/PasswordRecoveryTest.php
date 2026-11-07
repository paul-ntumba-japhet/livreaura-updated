<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\ChallengePurpose;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordRecoveryTest extends IdentityAccessTestCase
{
    public function test_unknown_email_returns_a_generic_accepted_response(): void
    {
        $email = $this->uniqueEmail();

        $this->postJson('/api/v1/identity-access/recuperations', [
            'email' => $email,
        ])->assertStatus(202)
            ->assertJsonPath('data.statut', 'recuperation_demandee');

        $this->assertSame(0, VerificationChallenge::query()->count());
        $this->assertNull(Account::query()->where('email_normalise', $email)->first());
    }

    public function test_closed_account_does_not_receive_a_challenge(): void
    {
        $this->seedServicePolicies();
        $account = Account::factory()->closed()->create();

        $this->postJson('/api/v1/identity-access/recuperations', [
            'email' => $account->email_normalise,
        ])->assertStatus(202);

        $this->assertFalse(
            VerificationChallenge::query()->where('compte_id', $account->id)->exists()
        );
    }

    public function test_eligible_account_can_reset_password_once(): void
    {
        $this->seedServicePolicies();
        $account = Account::factory()->active()->create([
            'hash_mdp' => Hash::make('ancien-secret'),
        ]);

        $this->postJson('/api/v1/identity-access/recuperations', [
            'email' => $account->email_normalise,
        ])->assertStatus(202);

        $proof = $this->issuedChallengeProof($account->id, 'password_reset');
        $secret = $proof['secret'];
        $challengeId = $proof['challenge_id'];

        $this->postJson('/api/v1/identity-access/recuperations/verification', [
            'defi_id' => $challengeId,
            'preuve' => $secret,
        ])->assertOk()
            ->assertJsonPath('data.formulaire', 'autorise');

        $this->assertNull(VerificationChallenge::query()->find($challengeId)?->consomme_a);

        $this->postJson('/api/v1/identity-access/recuperations/confirmation', [
            'defi_id' => $challengeId,
            'preuve' => $secret,
            'password' => 'nouveau-secret',
        ])->assertOk()
            ->assertJsonPath('data.prochaine_action', 'connexion');

        $this->assertNotNull(VerificationChallenge::query()->find($challengeId)?->consomme_a);
        $this->assertTrue(Hash::check('nouveau-secret', $account->fresh()->getAuthPassword()));
        $this->assertSame($account->statut, $account->fresh()->statut);

        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $account->email_normalise,
            'password' => 'ancien-secret',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $account->email_normalise,
            'password' => 'nouveau-secret',
        ])->assertOk();

        $this->postJson('/api/v1/identity-access/recuperations/confirmation', [
            'defi_id' => $challengeId,
            'preuve' => $secret,
            'password' => 'autre-secret1',
        ])->assertStatus(422);
    }

    public function test_activation_challenge_cannot_reset_password(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($text, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);
        $activationSecret = $proof['secret'];
        $activationId = $proof['challenge_id'];

        $this->postJson('/api/v1/identity-access/recuperations/verification', [
            'defi_id' => $activationId,
            'preuve' => $activationSecret,
        ])->assertStatus(422);

        $this->assertSame(
            ChallengePurpose::EmailActivation,
            VerificationChallenge::query()->find($activationId)?->finalite
        );
        $this->assertNull(VerificationChallenge::query()->find($activationId)?->consomme_a);
    }

    public function test_voluntary_password_change_revokes_sessions(): void
    {
        $this->seedServicePolicies();
        $account = Account::factory()->active()->create([
            'hash_mdp' => Hash::make('ancien-secret'),
        ]);

        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $account->email_normalise,
            'password' => 'ancien-secret',
        ])->assertOk();

        $this->assertTrue(
            AuthenticationSession::query()->where('compte_id', $account->id)->where('statut', 'active')->exists()
        );

        $this->putJson('/api/v1/identity-access/moi/mot-de-passe', [
            'password_actuel' => 'ancien-secret',
            'password' => 'nouveau-secret',
        ])->assertOk()
            ->assertJsonPath('data.prochaine_action', 'connexion');

        $this->assertFalse(
            AuthenticationSession::query()->where('compte_id', $account->id)->where('statut', 'active')->exists()
        );
        $this->getJson('/api/v1/identity-access/moi')->assertUnauthorized();

        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $account->email_normalise,
            'password' => 'nouveau-secret',
        ])->assertOk();
    }
}
