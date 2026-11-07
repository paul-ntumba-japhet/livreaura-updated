<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use Illuminate\Support\Carbon;

class SessionRevocationTest extends IdentityAccessTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_lists_own_sessions_without_the_reference_fingerprint(): void
    {
        $account = $this->activatedAccount();
        $current = $this->currentSession($account);
        $other = $this->extraSession($current);
        $stranger = AuthenticationSession::factory()->create([
            'config_acces_id' => $current->config_acces_id,
        ]);

        $response = $this->getJson('/api/v1/identity-access/moi/sessions')
            ->assertOk()
            ->assertJsonPath('data.sessions.0.courante', true)
            ->assertJsonPath('data.sessions.0.id', $current->id)
            ->assertJsonPath('data.sessions.1.id', $other->id)
            ->assertJsonPath('data.sessions.1.courante', false)
            ->assertJsonPath('data.sessions.1.statut', SessionStatus::Active->value);

        $payload = $response->json('data.sessions');
        $this->assertIsArray($payload);
        $this->assertCount(2, $payload);
        $this->assertArrayNotHasKey('empreinte_reference', $payload[0]);
        $ids = array_column($payload, 'id');
        $this->assertNotContains($stranger->id, $ids);
        $this->assertStringNotContainsString((string) $current->empreinte_reference, (string) $response->getContent());
    }

    public function test_revoking_the_current_session_leaves_other_sessions_active(): void
    {
        $account = $this->activatedAccount();
        $current = $this->currentSession($account);
        $other = $this->extraSession($current);

        $this->deleteJson('/api/v1/identity-access/moi/sessions/courante')->assertNoContent();
        $this->getJson('/api/v1/identity-access/moi')->assertUnauthorized();

        $this->assertSame(SessionStatus::Revoked, $current->fresh()?->statut);
        $this->assertSame(SessionStatus::Active, $other->fresh()?->statut);
        $this->assertSame(AccountStatus::Active, $account->fresh()?->statut);
        $this->assertSame(1, OutgoingMessage::query()->where('operation', 'A1-21')->where('type', 'session_acces_modifie')->count());
    }

    public function test_revoking_all_sessions_closes_the_access_and_replays_with_204(): void
    {
        $account = $this->activatedAccount();
        $current = $this->currentSession($account);
        $other = $this->extraSession($current);

        $this->deleteJson('/api/v1/identity-access/moi/sessions')->assertNoContent();
        $this->getJson('/api/v1/identity-access/moi')->assertUnauthorized();
        $this->deleteJson('/api/v1/identity-access/moi/sessions')->assertNoContent();

        $this->assertSame(SessionStatus::Revoked, $current->fresh()?->statut);
        $this->assertSame(SessionStatus::Revoked, $other->fresh()?->statut);
        $this->assertSame(AccountStatus::Active, $account->fresh()?->statut);
        $this->assertSame(1, OutgoingMessage::query()->where('operation', 'A1-21')->where('type', 'session_acces_modifie')->count());
    }

    public function test_expired_deadlines_are_refused_immediately_then_marked_expired(): void
    {
        $account = $this->activatedAccount();
        $current = $this->currentSession($account);
        $this->markSessionPastInactivity($current);

        $this->getJson('/api/v1/identity-access/moi')->assertUnauthorized();
        $this->assertSame(SessionStatus::Active, $current->fresh()?->statut);

        $this->artisan('a1:expire-sessions')->assertSuccessful();

        $this->assertSame(SessionStatus::Expired, $current->fresh()?->statut);
        $this->assertSame(AccountStatus::Active, $account->fresh()?->statut);
        $this->assertSame(1, OutgoingMessage::query()->where('operation', 'A1-21')->where('cle_idempotence', 'like', 'a1-21:expiration:'.$current->id.'%')->count());

        $this->artisan('a1:expire-sessions')->assertSuccessful();
        $this->assertSame(1, OutgoingMessage::query()->where('operation', 'A1-21')->where('cle_idempotence', 'like', 'a1-21:expiration:'.$current->id.'%')->count());
    }

    public function test_activity_extends_inactivity_without_passing_the_absolute_deadline(): void
    {
        $account = $this->activatedAccount();
        $current = $this->currentSession($account);
        $originalInactivity = $current->expire_inactivite_a?->copy();
        $absolute = $current->expire_absolue_a?->copy();
        $this->assertNotNull($originalInactivity);
        $this->assertNotNull($absolute);

        Carbon::setTestNow(now()->addMinutes(5));
        $this->getJson('/api/v1/identity-access/moi')->assertOk();

        $fresh = $current->fresh();
        $this->assertNotNull($fresh);
        $this->assertTrue($fresh->expire_inactivite_a?->gt($originalInactivity));
        $this->assertTrue($fresh->expire_inactivite_a?->lte($absolute));
        $this->assertSame($absolute->toIso8601String(), $fresh->expire_absolue_a?->toIso8601String());
    }

    public function test_expiration_does_not_revive_a_revoked_session(): void
    {
        $account = $this->activatedAccount();
        $current = $this->currentSession($account);
        $this->deleteJson('/api/v1/identity-access/moi/sessions/courante')->assertNoContent();

        $revoked = $current->fresh();
        $this->assertNotNull($revoked);
        $this->markSessionPastInactivity($revoked);

        $this->artisan('a1:expire-sessions')->assertSuccessful();
        $this->assertSame(SessionStatus::Revoked, $revoked->fresh()?->statut);
    }

    private function currentSession(Account $account): AuthenticationSession
    {
        $session = AuthenticationSession::query()
            ->where('compte_id', $account->id)
            ->where('statut', SessionStatus::Active)
            ->orderByDesc('ouverte_a')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($session);

        return $session;
    }

    private function extraSession(AuthenticationSession $current): AuthenticationSession
    {
        return AuthenticationSession::factory()->create([
            'compte_id' => $current->compte_id,
            'config_acces_id' => $current->config_acces_id,
            'contexte_client' => 'Navigateur de test',
        ]);
    }

    private function markSessionPastInactivity(AuthenticationSession $session): void
    {
        $opened = now()->subHours(2);
        $session->ouverte_a = $opened;
        $session->derniere_activite_a = now()->subHour();
        $session->expire_inactivite_a = now()->subMinute();
        $session->expire_absolue_a = now()->addHours(10);
        $session->save();
    }
}
