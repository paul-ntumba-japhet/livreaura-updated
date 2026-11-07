<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;
use Illuminate\Support\Str;

class CloseAccountTest extends IdentityAccessTestCase
{
    public function test_reviewer_closes_an_account_revokes_sessions_and_replay_is_idempotent(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['cloturer_compte']);
        $demand = $this->verifiedClosureDemand($target->id, $reviewer->id);

        $this->loginAs($target->email_normalise);
        $this->assertGreaterThan(0, AuthenticationSession::query()->where('compte_id', $target->id)->where('statut', SessionStatus::Active)->count());

        $this->loginAs($reviewer->email_normalise);
        $key = (string) Str::uuid();
        $first = $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/cloture", [
            'demande_id' => $demand->reference,
            'motif' => 'Cloture apres demande verifiee.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'cloture')
            ->assertJsonPath('data.prochaine_action', 'conservation')
            ->assertJsonPath('data.compte.statut', 'closed');

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/cloture", [
            'demande_id' => $demand->reference,
            'motif' => 'Cloture apres demande verifiee.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.compte.id', $target->id);

        $this->assertSame(AccountStatus::Closed, $target->fresh()?->statut);
        $this->assertNotNull($target->fresh()?->ferme_a);
        $this->assertNotNull($demand->fresh()?->clos_a);
        $this->assertSame(0, AuthenticationSession::query()->where('compte_id', $target->id)->where('statut', SessionStatus::Active)->count());
        $this->assertSame(0, VerificationChallenge::query()->where('compte_id', $target->id)->whereNull('revoque_a')->whereNull('consomme_a')->count());
        $this->assertSame('compte_acces_modifie', OutgoingMessage::query()->where('operation', 'A1-24')->where('cle_idempotence', 'like', 'a1-24:cloture:%')->value('type'));
        $this->assertNotNull(AccountEvent::query()->where('compte_sujet_id', $target->id)->where('auteur_compte_id', $reviewer->id)->where('type', 'compte_acces_modifie')->first());
        $this->assertNotSame('', $first->json('correlation_id'));

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $target->email_normalise,
            'password' => 'password12',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'authentification_refusee');
    }

    public function test_principal_membership_blocks_closure(): void
    {
        $principal = $this->activatedAccount();
        $type = $this->seedOrganizationType();
        $this->loginAs($principal->email_normalise);
        $created = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['cloturer_compte']);
        $demand = $this->verifiedClosureDemand($principal->id, $reviewer->id);

        $this->loginAs($reviewer->email_normalise);
        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$principal->id}/cloture", [
            'demande_id' => $demand->reference,
            'motif' => 'Tentative de cloture d un principal.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'representation_non_resolue');

        $this->assertSame(AccountStatus::Active, $principal->fresh()?->statut);
        $this->assertSame(1, OrganizationMembership::query()->where('compte_id', $principal->id)->where('principal', 1)->where('statut', 'active')->count());
        $this->assertSame('active', \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization::query()->where('id', $created->json('data.organisation.id'))->value('statut_operationnel'));
        $this->assertNull($demand->fresh()?->clos_a);
    }

    public function test_closure_requires_the_internal_permission(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['controler_kyc']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/cloture", [
            'demande_id' => 'CL-ABCDEFGHIJ',
            'motif' => 'Sans attribution de cloture.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    public function test_operator_cannot_close_self(): void
    {
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['cloturer_compte']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$reviewer->id}/cloture", [
            'demande_id' => 'CL-ABCDEFGHIJ',
            'motif' => 'Auto-cloture interdite.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }

    public function test_already_closed_account_cannot_be_closed_again(): void
    {
        $target = $this->activatedAccount();
        $target->statut = AccountStatus::Closed;
        $target->ferme_a = now();
        $target->save();

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['cloturer_compte']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/cloture", [
            'demande_id' => 'CL-ABCDEFGHIJ',
            'motif' => 'Compte deja cloture.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }

    private function verifiedClosureDemand(string $accountId, string $verifierId): SupportRequest
    {
        $demande = new SupportRequest;
        $demande->id = IdentityUlid::make();
        $demande->reference = 'CL-'.substr(IdentityUlid::make(), -10);
        $demande->type = 'cloture';
        $demande->compte_concerne_id = $accountId;
        $demande->demandeur_compte_id = $accountId;
        $demande->cree_a = now();
        $demande->identite_etat = 'verifiee';
        $demande->identite_verifiee_par = $verifierId;
        $demande->identite_verifiee_a = now();
        $demande->methode_verification = 'pieces';
        $demande->save();

        return $demande;
    }
}
