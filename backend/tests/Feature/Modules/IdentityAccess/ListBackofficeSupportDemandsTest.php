<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use Illuminate\Support\Str;

class ListBackofficeSupportDemandsTest extends IdentityAccessTestCase
{
    public function test_lists_open_support_demands(): void
    {
        $this->seedServicePolicies();
        $reference = $this->openPublicAppeal('inconnu.file.support@example.test');

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['traiter_support']);

        $response = $this->getJson('/api/v1/backoffice/identity-access/recours?per_page=50')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'a_traiter')
            ->assertJsonPath('data.pagination.per_page', 50);

        $this->getJson('/api/v1/backoffice/identity-access/recours')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 5);

        $payload = (string) json_encode($response->json('data'));
        $this->assertStringNotContainsString('hash_mdp', $payload);
        $this->assertStringNotContainsString('contact_chiffre', $payload);
        $this->assertStringNotContainsString('inconnu.file.support@example.test', $payload);

        $row = collect($response->json('data.demandes'))->firstWhere('reference', $reference);
        $this->assertIsArray($row);
        $this->assertSame('recours_suspension', $row['type']);
        $this->assertSame('non_verifiee', $row['identite_etat']);
        $this->assertTrue($row['demandeur']['externe'] ?? false);
        $this->assertNotEmpty($row['demandeur']['email_masque'] ?? null);
        $this->assertSame('a_affecter', $row['tache']['etat_travail'] ?? null);
        $this->assertGreaterThanOrEqual(1, $response->json('data.totaux.ouvertes'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.totaux.a_traiter'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.totaux.non_affectes'));
    }

    public function test_filters_search_tabs_type_and_priority(): void
    {
        $this->seedServicePolicies();
        $reference = $this->openPublicAppeal('inconnu.file.filtre@example.test');
        $demandeId = SupportRequest::query()->where('reference', $reference)->value('id');

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['traiter_support']);

        $found = collect($this->getJson('/api/v1/backoffice/identity-access/recours?q='.urlencode($reference))
            ->assertOk()
            ->json('data.demandes'))
            ->pluck('reference')
            ->all();
        $this->assertContains($reference, $found);

        $this->getJson('/api/v1/backoffice/identity-access/recours?type=recours_suspension')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'a_traiter');
        $this->getJson('/api/v1/backoffice/identity-access/recours?priorite=normale')
            ->assertOk();
        $this->getJson('/api/v1/backoffice/identity-access/recours?onglet=cloturees')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'cloturees');

        $task = ServiceTask::query()->where('demande_support_id', $demandeId)->first();
        $this->assertNotNull($task);
        $task->responsable_id = $reviewer->id;
        $task->etat_travail = 'en_cours';
        $task->save();

        $mine = collect($this->getJson('/api/v1/backoffice/identity-access/recours?onglet=mes_demandes')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'mes_demandes')
            ->json('data.demandes'))
            ->pluck('reference')
            ->all();
        $this->assertContains($reference, $mine);
        $this->assertGreaterThanOrEqual(1, $this->getJson('/api/v1/backoffice/identity-access/recours')->json('data.totaux.mes_demandes'));
    }

    public function test_refuses_the_directory_without_permission(): void
    {
        $this->activatedAccount();

        $this->getJson('/api/v1/backoffice/identity-access/recours')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    private function openPublicAppeal(string $email): string
    {
        $opened = $this->postJson('/api/v1/identity-access/recours', [
            'email' => $email,
            'message' => 'Je n arrive plus a ouvrir une session normale.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(202);

        $reference = $opened->json('data.reference');
        $this->assertIsString($reference);
        $this->assertNotSame('', $reference);

        return $reference;
    }
}
