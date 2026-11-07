<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Consent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ConsentEvent;
use Illuminate\Support\Str;

class ConsentResponseTest extends IdentityAccessTestCase
{
    public function test_guest_cannot_record_a_consent(): void
    {
        $text = $this->requiredLegalText();

        $this->postJson('/api/v1/identity-access/moi/consentements', [
            'texte_version_id' => $text->id,
            'nature' => 'acceptation',
        ])->assertUnauthorized();
    }

    public function test_new_required_version_is_recorded_without_a_second_event_on_replay(): void
    {
        $this->seedServicePolicies();
        $initial = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($initial, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);

        $this->postJson('/api/v1/identity-access/activation', [
            'defi_id' => $proof['challenge_id'],
            'preuve' => $proof['secret'],
        ])->assertOk();

        $this->loginAs($email);

        $next = $this->publishedLegalText('cgu', true);

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.textes_en_attente.0.id', $next->id);

        $first = $this->postJson('/api/v1/identity-access/moi/consentements', [
            'texte_version_id' => $next->id,
            'nature' => 'acceptation',
        ])->assertOk()
            ->assertJsonPath('data.consentement.texte_version_id', $next->id)
            ->assertJsonPath('data.consentement.retire_a', null);

        $this->postJson('/api/v1/identity-access/moi/consentements', [
            'texte_version_id' => $next->id,
            'nature' => 'acceptation',
        ])->assertOk()
            ->assertJsonPath('data.consentement.id', $first->json('data.consentement.id'));

        $consentId = $first->json('data.consentement.id');
        $this->assertSame(1, Consent::query()->where('compte_id', $account->id)->where('texte_version_id', $next->id)->count());
        $this->assertSame(1, ConsentEvent::query()->where('consentement_id', $consentId)->count());

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonMissing(['data' => ['textes_en_attente' => [['id' => $next->id]]]]);
    }

    public function test_optional_withdrawal_keeps_history_and_does_not_block_the_account(): void
    {
        $this->seedServicePolicies();
        $required = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($required, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);

        $this->postJson('/api/v1/identity-access/activation', [
            'defi_id' => $proof['challenge_id'],
            'preuve' => $proof['secret'],
        ])->assertOk();

        $this->loginAs($email);

        $optional = $this->publishedLegalText('newsletter', false);

        $this->postJson('/api/v1/identity-access/moi/consentements', [
            'texte_version_id' => $optional->id,
            'nature' => 'consentement_optionnel',
        ])->assertOk();

        $consent = Consent::query()
            ->where('compte_id', $account->id)
            ->where('texte_version_id', $optional->id)
            ->first();
        $this->assertNotNull($consent);

        $this->postJson('/api/v1/identity-access/moi/consentements', [
            'texte_version_id' => $optional->id,
            'action' => 'retrait',
        ])->assertOk()
            ->assertJsonPath('data.consentement.id', $consent->id);

        $this->assertNotNull($consent->fresh()->retire_a);
        $this->assertSame(AccountStatus::Active, $account->fresh()->statut);

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.compte.statut', 'active');

        $this->postJson('/api/v1/identity-access/moi/consentements', [
            'texte_version_id' => $optional->id,
            'nature' => 'consentement_optionnel',
        ])->assertOk()
            ->assertJsonPath('data.consentement.retire_a', null);

        $this->assertSame(1, Consent::query()->where('id', $consent->id)->count());
        $this->assertEqualsCanonicalizing(
            ['acceptation', 'retrait', 'reacceptation'],
            ConsentEvent::query()->where('consentement_id', $consent->id)->orderBy('date')->pluck('type')->all(),
        );
        $this->assertNull($consent->fresh()->retire_a);
    }

    public function test_required_text_cannot_be_withdrawn(): void
    {
        $this->seedServicePolicies();
        $required = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($required, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);

        $this->postJson('/api/v1/identity-access/activation', [
            'defi_id' => $proof['challenge_id'],
            'preuve' => $proof['secret'],
        ])->assertOk();

        $this->loginAs($email);

        $this->postJson('/api/v1/identity-access/moi/consentements', [
            'texte_version_id' => $required->id,
            'action' => 'retrait',
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'retrait_non_autorise');

        $this->assertSame(AccountStatus::Active, $account->fresh()->statut);
        $this->assertNull(
            Consent::query()
                ->where('compte_id', $account->id)
                ->where('texte_version_id', $required->id)
                ->value('retire_a'),
        );
    }

    public function test_pending_session_can_accept_a_text(): void
    {
        $this->seedServicePolicies();
        $required = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($required, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $this->loginAs($email);

        $optional = $this->publishedLegalText('newsletter', false);

        $this->postJson('/api/v1/identity-access/moi/consentements', [
            'texte_version_id' => $optional->id,
            'nature' => 'consentement_optionnel',
        ])->assertOk()
            ->assertJsonPath('data.consentement.nature', 'consentement_optionnel');
    }

    public function test_unknown_text_is_rejected(): void
    {
        $this->seedServicePolicies();
        $required = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($required, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);

        $this->postJson('/api/v1/identity-access/activation', [
            'defi_id' => $proof['challenge_id'],
            'preuve' => $proof['secret'],
        ])->assertOk();

        $this->loginAs($email);

        $this->postJson('/api/v1/identity-access/moi/consentements', [
            'texte_version_id' => strtoupper(str_pad('', 26, '0')),
            'nature' => 'acceptation',
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'texte_inconnu');
    }
}
