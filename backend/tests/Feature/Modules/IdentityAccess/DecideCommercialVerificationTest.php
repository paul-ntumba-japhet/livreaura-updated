<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Vendor;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationControl;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDecision;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DecideCommercialVerificationTest extends IdentityAccessTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kyc');
    }

    public function test_reviewer_approves_a_commercial_dossier_and_activates_a_vendor(): void
    {
        $this->activatedAccount();
        $dossierId = $this->submitCommercialDossier();

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['affecter_taches', 'controler_kyc', 'decider_kyc', 'decider_kyc_commercial']);

        $this->getJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}")
            ->assertOk()
            ->assertJsonPath('data.niveau_decision', 'partenaire_commercial')
            ->assertJsonPath('data.capacites.decider', false);

        $taskId = ServiceTask::query()->where('dossier_kyc_id', $dossierId)->value('id');
        $this->postJson("/api/v1/backoffice/identity-access/taches/{$taskId}/affectation")->assertOk();
        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/prise-en-charge")->assertOk();

        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/decision", [
            'resultat' => 'approved',
            'motif' => 'Decision commerciale hors perimetre A1-12.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'decision_commerciale_indisponible');

        $controls = $this->getJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}")
            ->assertOk()
            ->assertJsonPath('data.capacites.decider', true)
            ->json('data.controles');
        $this->assertNotEmpty($controls);

        foreach ($controls as $control) {
            $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/controles", [
                'controle_id' => $control['id'],
                'resultat' => 'passed',
                'observation' => 'Controle commercial consigne.',
            ])->assertOk();
        }

        $key = (string) Str::uuid();
        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/decision-commerciale", [
            'resultat' => 'approved',
            'motif' => 'Identite, fiscal et reversement controles.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'approved')
            ->assertJsonPath('data.dossier.statut', 'approved')
            ->assertJsonPath('data.dossier.niveau', 'partenaire_commercial')
            ->assertJsonPath('data.habilitation.niveau', 'partenaire_commercial')
            ->assertJsonPath('data.habilitation.etat', 'valid');

        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/decision-commerciale", [
            'resultat' => 'approved',
            'motif' => 'Identite, fiscal et reversement controles.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.dossier.id', $dossierId);

        $this->assertSame(1, VerificationDecision::query()->where('dossier_id', $dossierId)->count());
        $this->assertSame(1, VerificationCapability::query()->where('niveau', 'partenaire_commercial')->count());
        $this->assertSame(1, Vendor::query()->where('actif', 1)->count());
        $this->assertSame(
            'habilitation_commerciale_modifiee',
            OutgoingMessage::query()->where('operation', 'A1-13')->value('type'),
        );
        $this->assertSame('terminee', ServiceTask::query()->where('dossier_kyc_id', $dossierId)->value('etat_travail'));
    }

    public function test_commercial_decision_requires_commercial_permission(): void
    {
        $this->activatedAccount();
        $dossierId = $this->submitCommercialDossier();

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

        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/decision-commerciale", [
            'resultat' => 'approved',
            'motif' => 'Sans attribution commerciale.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    public function test_depositor_cannot_self_approve_a_commercial_dossier(): void
    {
        $depositor = $this->activatedAccount();
        $this->grantInternalPermissions($depositor, ['affecter_taches', 'controler_kyc', 'decider_kyc_commercial']);
        $dossierId = $this->submitCommercialDossier();
        $taskId = ServiceTask::query()->where('dossier_kyc_id', $dossierId)->value('id');

        $this->postJson("/api/v1/backoffice/identity-access/taches/{$taskId}/affectation")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflit_interet');
    }

    public function test_organization_dossier_cannot_be_decided_on_a1_13(): void
    {
        $this->activatedAccount();
        $type = $this->seedOrganizationType();
        $this->seedVerificationRequirements([
            ['code' => 'identite_representant', 'volet' => 'identite', 'type_sujet' => 'organisation', 'niveau' => 'organisation_verifiee'],
        ]);

        $created = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison Hors Commerce',
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

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['affecter_taches', 'controler_kyc', 'decider_kyc', 'decider_kyc_commercial']);
        $taskId = ServiceTask::query()->where('dossier_kyc_id', $dossierId)->value('id');
        $this->postJson("/api/v1/backoffice/identity-access/taches/{$taskId}/affectation")->assertOk();
        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/prise-en-charge")->assertOk();

        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/decision-commerciale", [
            'resultat' => 'approved',
            'motif' => 'Decision organisationnelle hors perimetre A1-13.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'decision_organisationnelle_indisponible');
    }

    private function submitCommercialDossier(): string
    {
        $this->seedVerificationRequirements([
            ['code' => 'identite_personne', 'volet' => 'identite', 'type_sujet' => 'particulier', 'niveau' => 'partenaire_commercial'],
            ['code' => 'profil_fiscal', 'volet' => 'fiscal', 'type_sujet' => 'particulier', 'niveau' => 'partenaire_commercial'],
            ['code' => 'compte_reversement', 'volet' => 'reversement', 'type_sujet' => 'particulier', 'niveau' => 'partenaire_commercial'],
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

        $this->putJson("/api/v1/identity-access/kyc/dossiers/{$dossierId}", [
            'profil_fiscal' => [
                'pays_fiscal' => 'CD',
                'nom_legal' => 'Amina Kabila',
                'identifiant_fiscal' => 'IF-013',
            ],
            'compte_reversement' => [
                'canal' => 'virement',
                'titulaire' => 'Amina Kabila',
                'pays' => 'CD',
                'devise' => 'USD',
                'reference_prestataire' => 'REF-PAY-013',
                'affichage_masque' => 'Compte se terminant par 013',
            ],
        ])->assertOk()
            ->assertJsonPath('data.dossier.complet', true);

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
