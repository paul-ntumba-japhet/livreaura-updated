<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceSubstitute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Recette MPT §11 R01–R26.
 *
 * R01 AuthenticationTest / inscription idempotente
 * R02 AuthenticationTest consommation unique du défi
 * R03 AuthenticationTest quota d essais
 * R04 IdentityOutboxTest renvoi
 * R05 PasswordRecoveryTest reset vs suspension
 * R06 SessionRevocationTest référence
 * R07 ci-dessous : expiration lue sans scheduler
 * R08 ci-dessous + AccessScopeTest
 * R09 SuspendAccountTest
 * R10 TransferPrimaryRepresentativeTest
 * R11 SubmitVerificationDossierTest
 * R12 DecideOrganizationVerificationTest révocation
 * R13 DecideCommercialVerificationTest
 * R14 ReviewVerificationValidityTest
 * R15 hors A1 (grant documentaire A3)
 * R16 ReceiveAccountReviewInstructionTest (A6-22)
 * R17 IdentityOutboxTest crash avant dispatch
 * R18 IdentityOutboxTest timeout
 * R19 ReceiveAccountReviewInstructionTest ACK engage_local
 * R20 IdentitySchedulerCommandsTest purge
 * R21 CloseAccountTest principal
 * R22 ConsentResponseTest
 * R23 EnableAuthorPathTest
 * R24 conservation (SubmitVerificationDossierTest)
 * R25 GrantInternalPermissionTest / décisions idempotentes
 * R26 ci-dessous : suppléance sans permission
 */
class RecetteA1CampaignTest extends IdentityAccessTestCase
{
    public function test_r07_expired_challenge_is_refused_without_running_the_scheduler(): void
    {
        $this->seedServicePolicies();
        $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayloadForRequiredTexts($email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);

        Carbon::setTestNow(now()->addHours(25));

        try {
            $this->postJson('/api/v1/identity-access/activation', [
                'defi_id' => $proof['challenge_id'],
                'preuve' => $proof['secret'],
            ])->assertStatus(422)
                ->assertJsonPath('error.code', 'defi_invalide');

            $this->assertSame('queued', MessageTransmission::query()->find($proof['transmission_id'])?->etat);
            $this->assertNull($account->fresh()?->email_verifie_a);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_r08_limited_and_support_only_sessions_cannot_open_backoffice(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($text, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();
        $this->loginAs($email);

        $this->getJson('/api/v1/identity-access/moi')->assertOk();
        $this->getJson('/api/v1/backoffice/identity-access/taches')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'portee_insuffisante');

        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte', 'traiter_support', 'autoriser_recours']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/suspension", [
            'motif' => 'Mesure de securite humaine.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();

        $opened = $this->postJson('/api/v1/identity-access/recours', [
            'email' => $target->email_normalise,
            'message' => 'Demande de recours apres suspension.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(202);

        $this->loginAs($reviewer->email_normalise);
        $this->postJson('/api/v1/backoffice/identity-access/recours/'.$opened->json('data.reference').'/autorisation-session', [
            'compte_id' => $target->id,
            'motif' => 'Identite du titulaire verifiee sur pieces.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();

        $this->loginAs($target->email_normalise);
        $this->getJson('/api/v1/identity-access/moi')->assertOk();
        $this->getJson('/api/v1/backoffice/identity-access/taches')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'portee_insuffisante');
    }

    public function test_r26_substitute_without_permission_cannot_decide(): void
    {
        $holder = $this->activatedAccount();
        $substitute = $this->activatedAccount();
        $actor = $this->activatedAccount();
        $this->grantInternalPermissions($actor, ['affecter_taches']);

        $this->postJson('/api/v1/backoffice/identity-access/suppleances', [
            'titulaire_id' => $holder->id,
            'suppleant_id' => $substitute->id,
            'fonction' => 'kyc',
            'debut_a' => now()->toIso8601String(),
            'fin_a' => now()->addDays(2)->toIso8601String(),
            'motif' => 'Suppleance sans delegation de decision.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $this->assertTrue(
            ServiceSubstitute::query()->where('titulaire_id', $holder->id)->where('suppleant_id', $substitute->id)->exists()
        );
        $this->assertFalse((new HasInternalPermission)($substitute->fresh(), 'decider_kyc'));
        $this->assertFalse((new HasInternalPermission)($substitute->fresh(), 'controler_kyc'));
        $this->assertFalse((new HasInternalPermission)($substitute->fresh(), 'affecter_taches'));

        $this->loginAs($substitute->email_normalise);

        $this->postJson('/api/v1/backoffice/identity-access/kyc/'.str_repeat('0', 26).'/decision', [
            'resultat' => 'approved',
            'motif' => 'Decision par suppleance.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }
}
