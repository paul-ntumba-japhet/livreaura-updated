<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use Illuminate\Support\Str;

class AccessScopeTest extends IdentityAccessTestCase
{
    public function test_limited_session_keeps_account_access_and_is_refused_elsewhere(): void
    {
        $this->seedServicePolicies();
        $type = $this->seedOrganizationType();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($text, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $this->loginAs($email);

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.compte.statut', 'pending')
            ->assertJsonPath('data.session.portee', SessionAccessScope::Limited->value);

        $this->getJson('/api/v1/identity-access/moi/sessions')->assertOk();

        $this->assertScopeForbidden($this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ]));

        $this->assertScopeForbidden($this->postJson('/api/v1/identity-access/kyc/dossiers', [
            'niveau' => 'partenaire_commercial',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ]));

        $this->assertScopeForbidden($this->postJson('/api/v1/identity-access/moi/parcours-auteur', [], [
            'Idempotency-Key' => (string) Str::uuid(),
        ]));

        $this->assertScopeForbidden($this->putJson('/api/v1/identity-access/moi/mot-de-passe', [
            'password_actuel' => 'password12',
            'password' => 'password13',
        ]));

        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/taches'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/kyc'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/comptes'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/organisations'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/habilitations'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/recours'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/equipe'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/supervision'));
        $this->assertScopeForbidden($this->getJson('/api/v1/identity-access/support/REFUS-LIMITE'));
    }

    public function test_support_only_session_keeps_appeal_access_and_is_refused_elsewhere(): void
    {
        [$target, $reference] = $this->authorizedSupportOnlyLogin();

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.compte.id', $target->id)
            ->assertJsonPath('data.session.portee', SessionAccessScope::SupportOnly->value)
            ->assertJsonPath('data.recours.reference', $reference);

        $this->getJson('/api/v1/identity-access/moi/sessions')->assertOk();

        $this->getJson('/api/v1/identity-access/support/'.$reference)
            ->assertOk()
            ->assertJsonPath('data.demande.reference', $reference)
            ->assertJsonPath('data.capacites.notes_internes', false);

        $this->assertScopeForbidden($this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $this->seedOrganizationType()->id,
            'raison_sociale' => 'Maison du Fleuve',
            'pays' => 'CD',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ]));

        $this->assertScopeForbidden($this->postJson('/api/v1/identity-access/kyc/dossiers', [
            'niveau' => 'partenaire_commercial',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ]));

        $this->assertScopeForbidden($this->postJson('/api/v1/identity-access/moi/parcours-auteur', [], [
            'Idempotency-Key' => (string) Str::uuid(),
        ]));

        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/taches'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/kyc'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/comptes'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/organisations'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/habilitations'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/recours'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/equipe'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/transmissions'));
        $this->assertScopeForbidden($this->getJson('/api/v1/backoffice/identity-access/supervision'));
    }

    /**
     * @return array{0: \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account, 1: string}
     */
    private function authorizedSupportOnlyLogin(): array
    {
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

        $reference = $opened->json('data.reference');
        $this->assertNotNull($reference);

        $this->loginAs($reviewer->email_normalise);
        $this->postJson("/api/v1/backoffice/identity-access/recours/{$reference}/autorisation-session", [
            'compte_id' => $target->id,
            'motif' => 'Identite du titulaire verifiee sur pieces.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();

        $this->loginAs($target->email_normalise);

        return [$target, $reference];
    }

    private function assertScopeForbidden(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertForbidden()
            ->assertJsonPath('error.code', 'portee_insuffisante');
    }
}
