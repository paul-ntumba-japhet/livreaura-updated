<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use Illuminate\Support\Str;

class OpenSuspensionAppealTest extends IdentityAccessTestCase
{
    public function test_public_deposit_is_accepted_without_revealing_a_account(): void
    {
        $this->seedServicePolicies();
        $key = (string) Str::uuid();

        $first = $this->postJson('/api/v1/identity-access/recours', [
            'email' => 'inconnu.recours@example.test',
            'message' => 'Je n arrive plus a ouvrir une session normale.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertStatus(202)
            ->assertJsonPath('data.statut', 'recu')
            ->assertJsonPath('data.prochaine_action', 'conserver_la_reference');

        $reference = $first->json('data.reference');
        $this->assertNotSame('', $reference);
        $this->assertNull($first->json('data.compte'));
        $this->assertNull($first->json('data.session'));

        $this->postJson('/api/v1/identity-access/recours', [
            'email' => 'inconnu.recours@example.test',
            'message' => 'Je n arrive plus a ouvrir une session normale.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertStatus(202)
            ->assertJsonPath('data.reference', $reference);

        $this->assertSame(1, SupportRequest::query()->where('reference', $reference)->where('type', 'recours_suspension')->count());
        $this->assertSame('non_verifiee', SupportRequest::query()->where('reference', $reference)->value('identite_etat'));
        $this->assertSame('recours_ouvert', OutgoingMessage::query()->where('operation', 'A1-25')->where('cle_idempotence', 'like', 'a1-25:recours:%')->value('type'));
    }

    public function test_agent_authorizes_a_support_only_session_and_suspended_login_succeeds(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte', 'traiter_support', 'autoriser_recours']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/suspension", [
            'motif' => 'Mesure de securite humaine.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();

        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $target->email_normalise,
            'password' => 'password12',
        ])->assertUnauthorized();

        $opened = $this->postJson('/api/v1/identity-access/recours', [
            'email' => $target->email_normalise,
            'message' => 'Demande de recours apres suspension.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(202);

        $reference = $opened->json('data.reference');
        $key = (string) Str::uuid();

        $this->loginAs($reviewer->email_normalise);
        $authorized = $this->postJson("/api/v1/backoffice/identity-access/recours/{$reference}/autorisation-session", [
            'compte_id' => $target->id,
            'motif' => 'Identite du titulaire verifiee sur pieces.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'session_autorisee')
            ->assertJsonPath('data.prochaine_action', 'connexion_recours')
            ->assertJsonPath('data.session.portee', 'support_only')
            ->assertJsonMissingPath('data.session.reference');

        $this->postJson("/api/v1/backoffice/identity-access/recours/{$reference}/autorisation-session", [
            'compte_id' => $target->id,
            'motif' => 'Identite du titulaire verifiee sur pieces.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.demande.reference', $reference);

        $this->assertSame('verifiee', SupportRequest::query()->where('reference', $reference)->value('identite_etat'));
        $this->assertSame($target->id, SupportRequest::query()->where('reference', $reference)->value('compte_concerne_id'));
        $this->assertSame(1, AuthenticationSession::query()->where('compte_id', $target->id)->where('portee', SessionAccessScope::SupportOnly)->where('statut', SessionStatus::Active)->count());
        $this->assertNull($authorized->json('data.session.secret'));

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $target->email_normalise,
            'password' => 'password12',
        ])->assertOk()
            ->assertJsonPath('data.compte.statut', 'suspended')
            ->assertJsonPath('data.session.portee', 'support_only');

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.session.portee', 'support_only')
            ->assertJsonPath('data.recours.reference', $reference)
            ->assertJsonPath('data.recours.identite_etat', 'verifiee');
    }

    public function test_authorization_requires_both_support_permissions(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['traiter_support']);

        $opened = $this->postJson('/api/v1/identity-access/recours', [
            'email' => $target->email_normalise,
            'message' => 'Recours sans autorisation de session.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(202);

        $this->postJson('/api/v1/backoffice/identity-access/recours/'.$opened->json('data.reference').'/autorisation-session', [
            'compte_id' => $target->id,
            'motif' => 'Tentative sans autoriser_recours.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    public function test_operator_cannot_authorize_own_account(): void
    {
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['traiter_support', 'autoriser_recours']);

        $opened = $this->postJson('/api/v1/identity-access/recours', [
            'email' => $reviewer->email_normalise,
            'message' => 'Tentative d auto-autorisation de recours.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(202);

        $this->postJson('/api/v1/backoffice/identity-access/recours/'.$opened->json('data.reference').'/autorisation-session', [
            'compte_id' => $reviewer->id,
            'motif' => 'Auto-autorisation interdite.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }

    public function test_active_account_cannot_receive_a_support_only_session(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['traiter_support', 'autoriser_recours']);

        $opened = $this->postJson('/api/v1/identity-access/recours', [
            'email' => $target->email_normalise,
            'message' => 'Recours sur un compte encore actif.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(202);

        $this->postJson('/api/v1/backoffice/identity-access/recours/'.$opened->json('data.reference').'/autorisation-session', [
            'compte_id' => $target->id,
            'motif' => 'Identite supposee verifiee.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');

        $this->assertSame(AccountStatus::Active, $target->fresh()?->statut);
    }
}
