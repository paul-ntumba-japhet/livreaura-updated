<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListBackofficeKycDossiersTest extends IdentityAccessTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kyc');
    }

    public function test_lists_submitted_dossiers_to_review(): void
    {
        $this->activatedAccount();
        $dossierId = $this->submitOrganizationDossier();

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['controler_kyc', 'affecter_taches']);

        $response = $this->getJson('/api/v1/backoffice/identity-access/kyc?per_page=50')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'a_traiter')
            ->assertJsonPath('data.pagination.per_page', 50);

        $this->getJson('/api/v1/backoffice/identity-access/kyc')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 5);

        $payload = (string) json_encode($response->json('data'));
        $this->assertStringNotContainsString('hash_mdp', $payload);

        $row = collect($response->json('data.dossiers'))->firstWhere('id', $dossierId);
        $this->assertIsArray($row);
        $this->assertNotEmpty($row['numero']);
        $this->assertSame('organisation_verifiee', $row['niveau']);
        $this->assertSame('submitted', $row['statut']);
        $this->assertSame('Maison KYC File', $row['sujet']['libelle'] ?? null);
        $this->assertNull($row['responsable']);
        $this->assertGreaterThanOrEqual(1, $response->json('data.totaux.a_traiter'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.totaux.non_affectes'));
        $this->assertSame($dossierId, ServiceTask::query()->where('dossier_kyc_id', $dossierId)->value('dossier_kyc_id'));
    }

    public function test_filters_search_and_lists_assigned_dossiers(): void
    {
        $this->activatedAccount();
        $dossierId = $this->submitOrganizationDossier();

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['controler_kyc', 'affecter_taches']);

        $names = collect($this->getJson('/api/v1/backoffice/identity-access/kyc?q='.urlencode('Maison KYC File'))
            ->assertOk()
            ->json('data.dossiers'))
            ->pluck('sujet.libelle')
            ->all();
        $this->assertContains('Maison KYC File', $names);

        $taskId = ServiceTask::query()->where('dossier_kyc_id', $dossierId)->value('id');
        $this->assertNotEmpty($taskId);
        $this->postJson("/api/v1/backoffice/identity-access/taches/{$taskId}/affectation")->assertOk();

        $mine = $this->getJson('/api/v1/backoffice/identity-access/kyc?onglet=mes_dossiers')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'mes_dossiers');
        $this->assertContains($dossierId, collect($mine->json('data.dossiers'))->pluck('id')->all());
        $this->assertSame($reviewer->id, collect($mine->json('data.dossiers'))->firstWhere('id', $dossierId)['responsable']['id'] ?? null);
        $this->assertGreaterThanOrEqual(1, $mine->json('data.totaux.mes_dossiers'));

        $this->getJson('/api/v1/backoffice/identity-access/kyc?onglet=a_corriger')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'a_corriger');
        $this->getJson('/api/v1/backoffice/identity-access/kyc?onglet=historique')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'historique');
    }

    public function test_refuses_the_directory_without_permission(): void
    {
        $this->activatedAccount();

        $this->getJson('/api/v1/backoffice/identity-access/kyc')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    private function submitOrganizationDossier(): string
    {
        $type = $this->seedOrganizationType();
        $this->seedVerificationRequirements([
            ['code' => 'identite_representant', 'volet' => 'identite', 'type_sujet' => 'organisation', 'niveau' => 'organisation_verifiee'],
        ]);

        $created = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison KYC File',
            'pays' => 'CD',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $opened = $this->postJson('/api/v1/identity-access/kyc/dossiers', [
            'niveau' => 'organisation_verifiee',
            'organisation_id' => $created->json('data.organisation.id'),
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $dossierId = $opened->json('data.dossier.id');
        $this->post("/api/v1/identity-access/kyc/dossiers/{$dossierId}/pieces", [
            'type_doc' => 'identite_representant',
            'fichier' => $this->pdfUpload(),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $this->postJson("/api/v1/identity-access/kyc/dossiers/{$dossierId}/soumission", [], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();

        return $dossierId;
    }

    private function pdfUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('identite.pdf', "%PDF-1.1\n1 0 obj<<>>endobj\ntrailer<>\n%%EOF");
    }
}
