<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Support\Str;

class AuthenticationTest extends IdentityAccessTestCase
{
    public function test_pending_account_opens_a_limited_session(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $password = 'password12';

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($text, $email, $password),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $email,
            'password' => $password,
        ])->assertOk()
            ->assertJsonPath('data.compte.statut', 'pending')
            ->assertJsonPath('data.session.portee', SessionAccessScope::Limited->value);

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.compte.statut', 'pending')
            ->assertJsonPath('data.session.portee', SessionAccessScope::Limited->value)
            ->assertJsonPath('data.roles.0', 'reader');
    }

    public function test_active_account_opens_a_normal_session(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $password = 'password12';

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($text, $email, $password),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);
        $secret = $proof['secret'];
        $challengeId = $proof['challenge_id'];

        $this->postJson('/api/v1/identity-access/activation', [
            'defi_id' => $challengeId,
            'preuve' => $secret,
        ])->assertOk();

        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $email,
            'password' => $password,
        ])->assertOk()
            ->assertJsonPath('data.compte.statut', 'active')
            ->assertJsonPath('data.session.portee', SessionAccessScope::Normal->value);

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.compte.statut', 'active');

        $this->deleteJson('/api/v1/identity-access/moi/sessions/courante')
            ->assertNoContent();

        $this->getJson('/api/v1/identity-access/moi')->assertUnauthorized();
    }

    public function test_invalid_credentials_are_generic(): void
    {
        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $this->uniqueEmail(),
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'authentification_refusee');
    }
}
