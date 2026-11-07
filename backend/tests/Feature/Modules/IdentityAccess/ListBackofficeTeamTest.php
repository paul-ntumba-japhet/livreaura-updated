<?php

namespace Tests\Feature\Modules\IdentityAccess;

class ListBackofficeTeamTest extends IdentityAccessTestCase
{
    public function test_lists_agents_with_current_internal_permissions(): void
    {
        $agent = $this->activatedAccount();
        $this->grantInternalPermissions($agent, ['controler_kyc', 'decider_kyc']);

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['gerer_habilitations_internes']);

        $response = $this->getJson('/api/v1/backoffice/identity-access/equipe?per_page=50')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 50);

        $this->getJson('/api/v1/backoffice/identity-access/equipe')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 4);

        $payload = (string) json_encode($response->json('data'));
        $this->assertStringNotContainsString('hash_mdp', $payload);
        $this->assertStringNotContainsString($agent->email_normalise, $payload);

        $row = collect($response->json('data.agents'))->firstWhere('id', $agent->id);
        $this->assertIsArray($row);
        $this->assertContains('controler_kyc', $row['permissions'] ?? []);
        $this->assertSame(2, $row['permissions_actives'] ?? null);
        $this->assertNotEmpty($row['email_masque'] ?? null);
        $this->assertGreaterThanOrEqual(2, $response->json('data.totaux.agents_actifs'));
        $this->assertGreaterThanOrEqual(3, $response->json('data.totaux.attributions_actives'));
        $this->assertTrue($response->json('data.capacites.permissions'));
    }

    public function test_filters_search_and_function(): void
    {
        $agent = $this->activatedAccount();
        $this->grantInternalPermissions($agent, ['traiter_support']);
        $prenom = $agent->fresh()->profile?->prenom;
        $this->assertNotEmpty($prenom);

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['affecter_taches']);

        $names = collect($this->getJson('/api/v1/backoffice/identity-access/equipe?q='.urlencode((string) $prenom))
            ->assertOk()
            ->json('data.agents'))
            ->pluck('id')
            ->all();
        $this->assertContains($agent->id, $names);

        $support = collect($this->getJson('/api/v1/backoffice/identity-access/equipe?fonction=support')
            ->assertOk()
            ->json('data.agents'))
            ->pluck('id')
            ->all();
        $this->assertContains($agent->id, $support);

        $kyc = collect($this->getJson('/api/v1/backoffice/identity-access/equipe?fonction=kyc')
            ->assertOk()
            ->json('data.agents'))
            ->pluck('id')
            ->all();
        $this->assertNotContains($agent->id, $kyc);
        $this->assertFalse($this->getJson('/api/v1/backoffice/identity-access/equipe')->json('data.capacites.permissions'));
        $this->assertTrue($this->getJson('/api/v1/backoffice/identity-access/equipe')->json('data.capacites.suppleances'));
    }

    public function test_refuses_the_directory_without_permission(): void
    {
        $this->activatedAccount();

        $this->getJson('/api/v1/backoffice/identity-access/equipe')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }
}
