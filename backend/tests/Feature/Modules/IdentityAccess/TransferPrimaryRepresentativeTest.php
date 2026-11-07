<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use Illuminate\Support\Str;

class TransferPrimaryRepresentativeTest extends IdentityAccessTestCase
{
    public function test_reviewer_transfers_the_principal_and_replay_is_idempotent(): void
    {
        $outgoing = $this->activatedAccount();
        $organizationId = $this->createOrganization();
        $incoming = $this->activatedAccount();
        $this->assertNotSame($outgoing->id, $incoming->id);

        $this->loginAs($outgoing->email_normalise);
        $this->loginAs($incoming->email_normalise);

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['transferer_principal', 'verifier_representation']);

        $key = (string) Str::uuid();
        $first = $this->postJson("/api/v1/backoffice/identity-access/organisations/{$organizationId}/transfert-principal", [
            'nouveau_compte_id' => $incoming->id,
            'motif' => 'Mandat du nouveau representant controle.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'transfere')
            ->assertJsonPath('data.nouveau.compte_id', $incoming->id)
            ->assertJsonPath('data.nouveau.adhesion.principal', true)
            ->assertJsonPath('data.nouveau.adhesion.statut', 'active')
            ->assertJsonPath('data.nouveau.adhesion.permissions.0', 'deposer_kyc')
            ->assertJsonPath('data.ancien.compte_id', $outgoing->id)
            ->assertJsonPath('data.ancien.adhesion.principal', false)
            ->assertJsonPath('data.ancien.adhesion.statut', 'active')
            ->assertJsonPath('data.depart', false);

        $this->postJson("/api/v1/backoffice/identity-access/organisations/{$organizationId}/transfert-principal", [
            'nouveau_compte_id' => $incoming->id,
            'motif' => 'Mandat du nouveau representant controle.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.nouveau.adhesion.id', $first->json('data.nouveau.adhesion.id'));

        $this->assertSame(1, OrganizationMembership::query()->where('organisation_id', $organizationId)->where('principal', 1)->where('statut', 'active')->count());
        $this->assertSame('active', OrganizationMembership::query()->where('organisation_id', $organizationId)->where('compte_id', $outgoing->id)->value('statut'));
        $this->assertSame(0, OrganizationMembership::query()->where('id', $first->json('data.ancien.adhesion.id'))->first()?->permissions()->count());
        $this->assertSame(0, AuthenticationSession::query()->where('compte_id', $outgoing->id)->where('statut', SessionStatus::Active)->count());
        $this->assertSame(0, AuthenticationSession::query()->where('compte_id', $incoming->id)->where('statut', SessionStatus::Active)->count());
        $this->assertSame(1, AuthenticationSession::query()->where('compte_id', $reviewer->id)->where('statut', SessionStatus::Active)->count());

        $correlation = $first->json('correlation_id');
        $this->assertNotSame('', $correlation);
        $this->assertSame(2, AccountEvent::query()->where('type', 'adhesion_organisation_modifiee')->where('correlation', $correlation)->count());
        $this->assertSame('terminee', SupportRequest::query()->where('reference', $first->json('data.demande.reference'))->first()?->serviceTask?->etat_travail);
        $this->assertSame('verifiee', SupportRequest::query()->where('reference', $first->json('data.demande.reference'))->value('identite_etat'));
        $this->assertSame('representation', OutgoingMessage::query()->where('operation', 'A1-15')->where('cle_idempotence', 'like', 'a1-15:transfert:%')->value('type'));
    }

    public function test_departure_revokes_the_outgoing_membership(): void
    {
        $outgoing = $this->activatedAccount();
        $organizationId = $this->createOrganization();
        $incoming = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['transferer_principal', 'verifier_representation']);

        $this->postJson("/api/v1/backoffice/identity-access/organisations/{$organizationId}/transfert-principal", [
            'nouveau_compte_id' => $incoming->id,
            'motif' => 'Depart du representant sortant.',
            'depart' => true,
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('data.depart', true)
            ->assertJsonPath('data.ancien.adhesion.statut', 'revoked');

        $this->assertSame('revoked', OrganizationMembership::query()->where('organisation_id', $organizationId)->where('compte_id', $outgoing->id)->value('statut'));
        $this->assertSame(1, OrganizationMembership::query()->where('organisation_id', $organizationId)->where('principal', 1)->where('statut', 'active')->count());
    }

    public function test_transfer_requires_both_internal_permissions(): void
    {
        $this->activatedAccount();
        $organizationId = $this->createOrganization();
        $incoming = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['transferer_principal']);

        $this->postJson("/api/v1/backoffice/identity-access/organisations/{$organizationId}/transfert-principal", [
            'nouveau_compte_id' => $incoming->id,
            'motif' => 'Sans verification de representation.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    public function test_operator_cannot_transfer_to_or_from_self(): void
    {
        $outgoing = $this->activatedAccount();
        $organizationId = $this->createOrganization();
        $this->grantInternalPermissions($outgoing, ['transferer_principal', 'verifier_representation']);
        $incoming = $this->activatedAccount();
        $this->loginAs($outgoing->email_normalise);

        $this->postJson("/api/v1/backoffice/identity-access/organisations/{$organizationId}/transfert-principal", [
            'nouveau_compte_id' => $incoming->id,
            'motif' => 'Auto-transfert du sortant.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'conflit_interet');
    }

    public function test_pending_account_cannot_become_principal(): void
    {
        $this->activatedAccount();
        $organizationId = $this->createOrganization();
        $pending = $this->pendingAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['transferer_principal', 'verifier_representation']);

        $this->postJson("/api/v1/backoffice/identity-access/organisations/{$organizationId}/transfert-principal", [
            'nouveau_compte_id' => $pending->id,
            'motif' => 'Compte encore en attente.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'compte_ineligible');
    }

    private function createOrganization(): string
    {
        $type = $this->seedOrganizationType();
        $created = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
            'ville' => 'Kinshasa',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        return $created->json('data.organisation.id');
    }

    private function pendingAccount(): \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account
    {
        $this->seedServicePolicies();
        $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayloadForRequiredTexts($email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account::query()
            ->where('email_normalise', $email)
            ->first();
        $this->assertNotNull($account);

        return $account;
    }
}
