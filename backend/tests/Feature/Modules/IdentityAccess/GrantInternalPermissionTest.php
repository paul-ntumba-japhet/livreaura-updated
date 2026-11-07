<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use Illuminate\Support\Str;

class GrantInternalPermissionTest extends IdentityAccessTestCase
{
    public function test_grants_a_permission_without_replacing_a_previous_period(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['gerer_habilitations_internes']);

        $key = (string) Str::uuid();
        $this->postJson('/api/v1/backoffice/identity-access/permissions', [
            'compte_id' => $target->id,
            'permission' => 'controler_kyc',
            'motif' => 'Attribution pour le controle des dossiers.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'attribuee')
            ->assertJsonPath('data.attribution.permission', 'controler_kyc')
            ->assertJsonPath('data.attribution.etat', 'effective');

        $this->postJson('/api/v1/backoffice/identity-access/permissions', [
            'compte_id' => $target->id,
            'permission' => 'controler_kyc',
            'motif' => 'Attribution pour le controle des dossiers.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.attribution.permission', 'controler_kyc');

        $this->assertTrue((new HasInternalPermission)($target->fresh(), 'controler_kyc'));
        $this->assertSame(1, InternalAttribution::query()->where('compte_id', $target->id)->count());
        $this->assertSame(1, AccountEvent::query()->where('compte_sujet_id', $target->id)->where('type', 'permission_interne_modifiee')->count());
        $this->assertSame('permission_interne_modifiee', OutgoingMessage::query()->where('operation', 'permissions_internes')->value('type'));

        $this->postJson('/api/v1/backoffice/identity-access/permissions', [
            'compte_id' => $target->id,
            'permission' => 'controler_kyc',
            'motif' => 'Deuxieme attribution sans revocation.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }

    public function test_lists_catalog_and_history_for_a_known_account(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['gerer_habilitations_internes']);

        $this->postJson('/api/v1/backoffice/identity-access/permissions', [
            'compte_id' => $target->id,
            'permission' => 'decider_kyc',
            'motif' => 'Attribution pour decider les dossiers.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $this->getJson('/api/v1/backoffice/identity-access/permissions')
            ->assertOk()
            ->assertJsonPath('data.compte', null);

        $this->getJson('/api/v1/backoffice/identity-access/permissions?compte_id='.$target->id)
            ->assertOk()
            ->assertJsonPath('data.compte.id', $target->id)
            ->assertJsonPath('data.attributions.0.permission', 'decider_kyc')
            ->assertJsonPath('data.attributions.0.etat', 'effective');
    }

    public function test_self_grant_and_missing_permission_are_refused(): void
    {
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['gerer_habilitations_internes']);

        $this->postJson('/api/v1/backoffice/identity-access/permissions', [
            'compte_id' => $reviewer->id,
            'permission' => 'controler_kyc',
            'motif' => 'Auto promotion par formulaire.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');

        $other = $this->activatedAccount();
        $this->postJson('/api/v1/backoffice/identity-access/permissions', [
            'compte_id' => $reviewer->id,
            'permission' => 'controler_kyc',
            'motif' => 'Sans habilitation de gestion.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    public function test_revokes_immediately_then_allows_a_new_period(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['gerer_habilitations_internes']);

        $granted = $this->postJson('/api/v1/backoffice/identity-access/permissions', [
            'compte_id' => $target->id,
            'permission' => 'suspendre_compte',
            'motif' => 'Attribution temporaire de suspension.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $attributionId = $granted->json('data.attribution.id');
        $this->assertNotNull($attributionId);
        $this->assertTrue((new HasInternalPermission)($target->fresh(), 'suspendre_compte'));

        $key = (string) Str::uuid();
        $this->postJson("/api/v1/backoffice/identity-access/permissions/{$attributionId}/revocation", [
            'motif' => 'Fin de la periode d attribution.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'revoquee')
            ->assertJsonPath('data.attribution.etat', 'revoquee');

        $this->postJson("/api/v1/backoffice/identity-access/permissions/{$attributionId}/revocation", [
            'motif' => 'Fin de la periode d attribution.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'revoquee');

        $this->assertFalse((new HasInternalPermission)($target->fresh(), 'suspendre_compte'));

        $this->postJson("/api/v1/backoffice/identity-access/permissions/{$attributionId}/revocation", [
            'motif' => 'Seconde revocation.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');

        $this->postJson('/api/v1/backoffice/identity-access/permissions', [
            'compte_id' => $target->id,
            'permission' => 'suspendre_compte',
            'motif' => 'Nouvelle periode apres revocation.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated()
            ->assertJsonPath('data.attribution.etat', 'effective');

        $this->assertTrue((new HasInternalPermission)($target->fresh(), 'suspendre_compte'));
        $this->assertSame(2, InternalAttribution::query()->where('compte_id', $target->id)->count());
    }

    public function test_unknown_permission_and_past_deadline_are_rejected(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['gerer_habilitations_internes']);

        $this->postJson('/api/v1/backoffice/identity-access/permissions', [
            'compte_id' => $target->id,
            'permission' => 'permission_inventee',
            'motif' => 'Permission absente du referentiel.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertNotFound()
            ->assertJsonPath('error.code', 'permission_inconnue');

        $this->postJson('/api/v1/backoffice/identity-access/permissions', [
            'compte_id' => $target->id,
            'permission' => 'controler_kyc',
            'motif' => 'Echeance deja passee.',
            'expire_a' => now()->subHour()->toIso8601String(),
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'saisie_invalide');
    }
}
