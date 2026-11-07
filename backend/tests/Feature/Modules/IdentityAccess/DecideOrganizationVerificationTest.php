<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationControl;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDecision;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DecideOrganizationVerificationTest extends IdentityAccessTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kyc');
    }

    public function test_reviewer_assigns_controls_and_approves_an_organization_dossier(): void
    {
        $this->activatedAccount();
        $dossierId = $this->submitOrganizationDossier();

        $this->getJson('/api/v1/backoffice/identity-access/taches')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['affecter_taches', 'controler_kyc', 'decider_kyc']);

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonFragment(['controler_kyc']);

        $list = $this->getJson('/api/v1/backoffice/identity-access/taches')
            ->assertOk()
            ->assertJsonPath('data.taches.0.dossier.id', $dossierId)
            ->assertJsonPath('data.taches.0.tache.etat_travail', 'a_affecter');

        $taskId = $list->json('data.taches.0.tache.id');
        $this->assertNotEmpty($taskId);

        $this->postJson("/api/v1/backoffice/identity-access/taches/{$taskId}/affectation")
            ->assertOk()
            ->assertJsonPath('data.tache.responsable_id', $reviewer->id)
            ->assertJsonPath('data.capacites.prendre_en_charge', true);

        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/prise-en-charge")
            ->assertOk()
            ->assertJsonPath('data.dossier.statut', 'under_review')
            ->assertJsonPath('data.tache.etat_travail', 'en_cours');

        $review = $this->getJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}")
            ->assertOk();
        $controlId = $review->json('data.controles.0.id');
        $this->assertNotEmpty($controlId);

        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/decision", [
            'resultat' => 'approved',
            'motif' => 'Approbation trop tot.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'controles_incomplets');

        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/controles", [
            'controle_id' => $controlId,
            'resultat' => 'passed',
            'observation' => 'Identite du representant concordante.',
        ])->assertOk()
            ->assertJsonPath('data.controles.0.resultat', 'passed');

        $key = (string) Str::uuid();
        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/decision", [
            'resultat' => 'approved',
            'motif' => 'Existence, mandat et canal controles.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'approved')
            ->assertJsonPath('data.dossier.statut', 'approved')
            ->assertJsonPath('data.habilitation.etat', 'valid');

        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/decision", [
            'resultat' => 'approved',
            'motif' => 'Existence, mandat et canal controles.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.dossier.statut', 'approved');

        $this->assertSame(1, VerificationDecision::query()->where('dossier_id', $dossierId)->count());
        $this->assertSame('terminee', ServiceTask::query()->where('dossier_kyc_id', $dossierId)->value('etat_travail'));
        $this->assertSame(1, VerificationCapability::query()->where('niveau', 'organisation_verifiee')->count());
        $this->assertSame('passed', VerificationControl::query()->where('id', $controlId)->value('resultat'));
    }

    public function test_depositor_cannot_self_assign_even_with_permissions(): void
    {
        $depositor = $this->activatedAccount();
        $this->grantInternalPermissions($depositor, ['affecter_taches', 'controler_kyc', 'decider_kyc']);
        $dossierId = $this->submitOrganizationDossier();
        $taskId = ServiceTask::query()->where('dossier_kyc_id', $dossierId)->value('id');

        $this->postJson("/api/v1/backoffice/identity-access/taches/{$taskId}/affectation")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflit_interet');
    }

    public function test_commercial_dossier_cannot_be_decided_on_a1_12(): void
    {
        $this->activatedAccount();
        $this->seedVerificationRequirements([
            ['code' => 'identite_personne', 'volet' => 'identite', 'type_sujet' => 'particulier', 'niveau' => 'partenaire_commercial'],
        ]);

        $opened = $this->postJson('/api/v1/identity-access/kyc/dossiers', [
            'niveau' => 'partenaire_commercial',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();
        $dossierId = $opened->json('data.dossier.id');
        $this->post("/api/v1/identity-access/kyc/dossiers/{$dossierId}/pieces", [
            'type_doc' => 'identite_personne',
            'fichier' => $this->pdfUpload(),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();
        $this->postJson("/api/v1/identity-access/kyc/dossiers/{$dossierId}/soumission", [], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['affecter_taches', 'controler_kyc', 'decider_kyc']);
        $taskId = ServiceTask::query()->where('dossier_kyc_id', $dossierId)->value('id');
        $this->postJson("/api/v1/backoffice/identity-access/taches/{$taskId}/affectation")->assertOk();
        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/prise-en-charge")->assertOk();
        $controlId = VerificationControl::query()->where('dossier_id', $dossierId)->value('id');
        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/controles", [
            'controle_id' => $controlId,
            'resultat' => 'passed',
        ])->assertOk();

        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/decision", [
            'resultat' => 'approved',
            'motif' => 'Decision commerciale hors perimetre A1-12.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'decision_commerciale_indisponible');
    }

    private function submitOrganizationDossier(): string
    {
        $type = $this->seedOrganizationType();
        $this->seedVerificationRequirements([
            ['code' => 'identite_representant', 'volet' => 'identite', 'type_sujet' => 'organisation', 'niveau' => 'organisation_verifiee'],
        ]);

        $created = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison KYC Examen',
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
