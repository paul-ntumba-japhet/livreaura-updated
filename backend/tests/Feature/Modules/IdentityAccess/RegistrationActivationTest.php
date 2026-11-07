<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountRole;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Role;
use Illuminate\Support\Str;

class RegistrationActivationTest extends IdentityAccessTestCase
{
    public function test_parcours_are_listed(): void
    {
        $text = $this->requiredLegalText();

        $this->getJson('/api/v1/identity-access/parcours')
            ->assertOk()
            ->assertJsonPath('data.parcours.0.code', 'lecteur')
            ->assertJsonPath('data.parcours.1.code', 'auteur')
            ->assertJsonPath('data.parcours.2.code', 'representant')
            ->assertJsonPath('data.types_organisation.0.code', 'ecole')
            ->assertJsonPath('data.types_organisation.1.code', 'universite')
            ->assertJsonPath('data.types_organisation.2.code', 'bibliotheque')
            ->assertJsonPath('data.types_organisation.0.exigences.0.code', 'existence_ecole')
            ->assertJsonCount(3, 'data.types_organisation.0.exigences')
            ->assertJsonCount(3, 'data.types_organisation.3.exigences')
            ->assertJsonPath('data.types_organisation.3.code', 'editeur')
            ->assertJsonPath('data.exigences_auteur.0.code', 'identite_personne')
            ->assertJsonCount(3, 'data.exigences_auteur')
            ->assertJsonPath('data.textes_requis.0.id', $text->id)
            ->assertJsonPath('data.textes_requis.0.contenu', $text->contenu)
            ->assertJsonStructure(['correlation_id']);
    }

    public function test_registration_creates_pending_account_with_reader_only(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $response = $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($text, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        );

        $response->assertCreated()
            ->assertJsonPath('data.compte.statut', 'pending')
            ->assertJsonPath('data.prochaine_action', 'verification_email');

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $this->assertSame(AccountStatus::Pending, $account->statut);
        $this->assertNotNull($account->profile);
        $this->assertNull($account->email_verifie_a);

        $reader = Role::query()->where('code', 'reader')->first();
        $this->assertNotNull($reader);
        $this->assertTrue(
            AccountRole::query()
                ->where('compte_id', $account->id)
                ->where('role_id', $reader->id)
                ->where('statut', 'active')
                ->exists(),
        );
        $this->assertSame(1, AccountRole::query()->where('compte_id', $account->id)->count());
        $this->assertTrue(
            OutgoingMessage::query()->where('compte_id', $account->id)->where('type', 'compte_cree')->exists(),
        );
        $this->assertTrue(
            OutgoingMessage::query()->where('compte_id', $account->id)->where('type', 'defi_email_activation')->exists(),
        );
        $this->assertNull($this->notifications()->lastSecretFor($account->id));
    }

    public function test_representative_registration_keeps_reader_and_records_the_path(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            [...$this->registrationPayload($text, $email), 'parcours' => 'representant'],
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $this->loginAs($email);

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.parcours_inscription', 'representant')
            ->assertJsonPath('data.roles.0', 'reader')
            ->assertJsonPath('data.parcours_auteur.etat', 'non_active');
    }

    public function test_duplicate_email_does_not_create_a_partial_account(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $payload = $this->registrationPayload($text, $email);

        $this->postJson('/api/v1/identity-access/inscriptions', $payload, [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $count = Account::query()->where('email_normalise', $email)->count();

        $this->postJson('/api/v1/identity-access/inscriptions', $payload, [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'compte_existant');

        $this->assertSame($count, Account::query()->where('email_normalise', $email)->count());
    }

    public function test_author_writing_language_must_be_an_ascii_code(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson('/api/v1/identity-access/inscriptions', [
            ...$this->registrationPayload($text, $email),
            'parcours' => 'auteur',
            'nom_plume' => 'Molière',
            'langue_ecriture' => 'Français',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'saisie_invalide');

        $this->assertNull(Account::query()->where('email_normalise', $email)->first());
    }

    public function test_missing_required_consent_is_rejected(): void
    {
        $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson('/api/v1/identity-access/inscriptions', [
            'parcours' => 'lecteur',
            'email' => $email,
            'password' => 'password12',
            'prenom' => 'Amina',
            'nom' => 'Kabila',
            'pays' => 'CD',
            'consentements' => [],
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'textes_requis');

        $this->assertNull(Account::query()->where('email_normalise', $email)->first());
    }

    public function test_idempotent_retry_returns_the_same_account(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $payload = $this->registrationPayload($text, $email);
        $key = (string) Str::uuid();

        $first = $this->postJson('/api/v1/identity-access/inscriptions', $payload, [
            'Idempotency-Key' => $key,
        ])->assertCreated();

        $this->postJson('/api/v1/identity-access/inscriptions', $payload, [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.compte.id', $first->json('data.compte.id'));

        $this->assertSame(1, Account::query()->where('email_normalise', $email)->count());
    }

    public function test_activation_makes_account_active_without_login(): void
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
        $secret = $proof['secret'];
        $challengeId = $proof['challenge_id'];

        $this->getJson('/api/v1/identity-access/activation?defi_id='.$challengeId)
            ->assertOk()
            ->assertJsonPath('data.action', 'activation');

        $this->assertSame(AccountStatus::Pending, $account->fresh()->statut);

        $this->postJson('/api/v1/identity-access/activation', [
            'defi_id' => $challengeId,
            'preuve' => $secret,
        ])->assertOk()
            ->assertJsonPath('data.compte.statut', 'active')
            ->assertJsonPath('data.prochaine_action', 'connexion');

        $this->assertSame(AccountStatus::Active, $account->fresh()->statut);
        $this->assertNotNull($account->fresh()->email_verifie_a);

        $this->getJson('/api/v1/identity-access/moi')->assertUnauthorized();

        $this->postJson('/api/v1/identity-access/activation', [
            'defi_id' => $challengeId,
            'preuve' => $secret,
        ])->assertStatus(409);
    }

    public function test_challenge_resend_is_generic(): void
    {
        $this->postJson('/api/v1/identity-access/defis/renvoi', [
            'email' => $this->uniqueEmail(),
        ])->assertStatus(202)
            ->assertJsonPath('data.statut', 'verification_demandee');
    }
}
