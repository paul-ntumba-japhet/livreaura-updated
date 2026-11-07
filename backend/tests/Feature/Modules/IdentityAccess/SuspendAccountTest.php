<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use Illuminate\Support\Str;

class SuspendAccountTest extends IdentityAccessTestCase
{
    public function test_reviewer_suspends_an_account_revokes_sessions_and_replay_is_idempotent(): void
    {
        $target = $this->activatedAccount();
        $this->loginAs($target->email_normalise);

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte']);

        $deadline = now()->addDays(7)->toIso8601String();
        $key = (string) Str::uuid();
        $first = $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/suspension", [
            'motif' => 'Decision humaine de securite.',
            'fin_prevue' => $deadline,
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'suspendu')
            ->assertJsonPath('data.prochaine_action', 'propagation')
            ->assertJsonPath('data.propagation', 'en_cours')
            ->assertJsonPath('data.compte.statut', 'suspended');

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/suspension", [
            'motif' => 'Decision humaine de securite.',
            'fin_prevue' => $deadline,
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.compte.id', $target->id)
            ->assertJsonPath('data.statut', 'suspendu');

        $this->assertSame(AccountStatus::Suspended, $target->fresh()?->statut);
        $this->assertSame(0, AuthenticationSession::query()->where('compte_id', $target->id)->where('statut', SessionStatus::Active)->count());
        $this->assertSame(1, AuthenticationSession::query()->where('compte_id', $reviewer->id)->where('statut', SessionStatus::Active)->count());

        $event = AccountEvent::query()
            ->where('compte_sujet_id', $target->id)
            ->where('type', 'compte_acces_modifie')
            ->where('auteur_compte_id', $reviewer->id)
            ->orderByDesc('date')
            ->first();
        $this->assertNotNull($event);
        $this->assertNull($event->origine_systeme);
        $this->assertSame('Decision humaine de securite.', $event->motif);

        $this->assertSame('compte_acces_modifie', OutgoingMessage::query()->where('operation', 'A1-22')->where('cle_idempotence', 'like', 'a1-22:suspension:%')->value('type'));
        $this->assertSame('session_acces_modifie', OutgoingMessage::query()->where('operation', 'A1-22')->where('type', 'session_acces_modifie')->value('type'));
        $this->assertNotSame('', $first->json('correlation_id'));

        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $target->email_normalise,
            'password' => 'password12',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'authentification_refusee');
    }

    public function test_suspending_a_principal_restricts_the_organization_without_suspending_members(): void
    {
        $principal = $this->activatedAccount();
        $type = $this->seedOrganizationType();
        $created = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();
        $organizationId = $created->json('data.organisation.id');

        $member = $this->activatedAccount();
        $membership = new OrganizationMembership;
        $membership->id = IdentityUlid::make();
        $membership->compte_id = $member->id;
        $membership->organisation_id = $organizationId;
        $membership->fonction = 'Membre';
        $membership->principal = 0;
        $membership->statut = 'active';
        $membership->origine = 'test';
        $membership->cree_a = now();
        $membership->active_a = now();
        $membership->save();

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$principal->id}/suspension", [
            'motif' => 'Principal indisponible jusqu au transfert.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('data.organisations_restreintes.0.id', $organizationId)
            ->assertJsonPath('data.organisations_restreintes.0.statut_operationnel', 'restricted');

        $this->assertSame(AccountStatus::Suspended, $principal->fresh()?->statut);
        $this->assertSame(AccountStatus::Active, $member->fresh()?->statut);
        $this->assertSame('restricted', Organization::query()->where('id', $organizationId)->value('statut_operationnel'));
        $this->assertSame('active', OrganizationMembership::query()->where('organisation_id', $organizationId)->where('compte_id', $principal->id)->value('statut'));
        $this->assertSame('active', OrganizationMembership::query()->where('organisation_id', $organizationId)->where('compte_id', $member->id)->value('statut'));
        $this->assertSame(1, OrganizationMembership::query()->where('organisation_id', $organizationId)->where('principal', 1)->where('statut', 'active')->count());
    }

    public function test_suspension_requires_the_internal_permission(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['controler_kyc']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/suspension", [
            'motif' => 'Sans attribution de suspension.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    public function test_operator_cannot_suspend_self(): void
    {
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$reviewer->id}/suspension", [
            'motif' => 'Auto-suspension interdite.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }

    public function test_already_suspended_account_cannot_be_suspended_again(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/suspension", [
            'motif' => 'Premiere suspension consignee.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/suspension", [
            'motif' => 'Deuxieme suspension distincte.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }

    public function test_closed_account_cannot_be_suspended(): void
    {
        $target = $this->activatedAccount();
        $target->statut = AccountStatus::Closed;
        $target->ferme_a = now();
        $target->save();

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/suspension", [
            'motif' => 'Compte deja cloture.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }

    public function test_unknown_account_is_not_visible(): void
    {
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte']);
        $unknownId = IdentityUlid::make();

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$unknownId}/suspension", [
            'motif' => 'Compte introuvable pour suspension.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertNotFound()
            ->assertJsonPath('error.code', 'compte_inconnu');
    }
}
