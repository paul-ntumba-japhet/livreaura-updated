<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationControl;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubmitVerificationDossierTest extends IdentityAccessTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kyc');
    }

    public function test_guest_cannot_open_a_dossier(): void
    {
        $this->postJson('/api/v1/identity-access/kyc/dossiers', [
            'niveau' => 'partenaire_commercial',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertUnauthorized();
    }

    public function test_pending_account_is_ineligible(): void
    {
        $this->seedServicePolicies();
        $text = $this->requiredLegalText();
        $email = $this->uniqueEmail();
        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayload($text, $email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();
        $this->loginAs($email);

        $this->postJson('/api/v1/identity-access/kyc/dossiers', [
            'niveau' => 'partenaire_commercial',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'portee_insuffisante');
    }

    public function test_individual_can_open_complete_and_submit_a_commercial_dossier(): void
    {
        $account = $this->activatedAccount();
        $this->seedCommercialRequirements();

        $opened = $this->postJson('/api/v1/identity-access/kyc/dossiers', [
            'niveau' => 'partenaire_commercial',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'draft')
            ->assertJsonPath('data.dossier.statut', 'draft')
            ->assertJsonPath('data.dossier.complet', false);

        $dossierId = $opened->json('data.dossier.id');
        $this->assertNotEmpty($dossierId);

        $this->postJson("/api/v1/identity-access/kyc/dossiers/{$dossierId}/soumission", [], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'dossier_incomplet');

        $this->post("/api/v1/identity-access/kyc/dossiers/{$dossierId}/pieces", [
            'type_doc' => 'identite_personne',
            'fichier' => $this->pdfUpload(),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated()
            ->assertJsonPath('data.piece.type_doc', 'identite_personne');

        $this->putJson("/api/v1/identity-access/kyc/dossiers/{$dossierId}", [
            'profil_fiscal' => [
                'pays_fiscal' => 'CD',
                'nom_legal' => 'Amina Kabila',
                'identifiant_fiscal' => 'IF-001',
            ],
            'compte_reversement' => [
                'canal' => 'virement',
                'titulaire' => 'Amina Kabila',
                'pays' => 'CD',
                'devise' => 'USD',
                'reference_prestataire' => 'REF-PAY-001',
                'affichage_masque' => 'Compte se terminant par 001',
            ],
        ])->assertOk()
            ->assertJsonPath('data.dossier.complet', true);

        $key = (string) Str::uuid();
        $submitted = $this->postJson("/api/v1/identity-access/kyc/dossiers/{$dossierId}/soumission", [], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'submitted')
            ->assertJsonPath('data.prochaine_action', 'attente_examen')
            ->assertJsonPath('data.dossier.statut', 'submitted');

        $this->postJson("/api/v1/identity-access/kyc/dossiers/{$dossierId}/soumission", [], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.dossier.id', $dossierId);

        $this->assertSame(3, VerificationControl::query()->where('dossier_id', $dossierId)->count());
        $this->assertSame(1, ServiceTask::query()->where('dossier_kyc_id', $dossierId)->where('etat_travail', 'a_affecter')->count());
        $this->assertNull(ServiceTask::query()->where('dossier_kyc_id', $dossierId)->value('demande_support_id'));

        $this->putJson("/api/v1/identity-access/kyc/dossiers/{$dossierId}", [
            'profil_fiscal' => [
                'pays_fiscal' => 'CD',
                'nom_legal' => 'Autre',
            ],
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'dossier_fige');

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.dossiers_verification.0.id', $dossierId)
            ->assertJsonPath('data.dossiers_verification.0.statut', 'submitted');

        $pieceId = $submitted->json('data.dossier.pieces.0.id');
        $download = $this->get("/api/v1/identity-access/kyc/dossiers/{$dossierId}/pieces/{$pieceId}");
        $download->assertOk();
        $cacheControl = strtolower((string) $download->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertSame($account->id, VerificationDossier::query()->where('id', $dossierId)->value('deposant_id'));
    }

    public function test_organization_dossier_requires_deposit_permission(): void
    {
        $this->activatedAccount();
        $type = $this->seedOrganizationType();
        $this->seedVerificationRequirements([
            ['code' => 'identite_representant', 'volet' => 'identite', 'type_sujet' => 'organisation', 'niveau' => 'organisation_verifiee'],
        ]);

        $this->postJson('/api/v1/identity-access/kyc/dossiers', [
            'niveau' => 'organisation_verifiee',
            'organisation_id' => str_pad('01', 26, 'A'),
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422);

        $created = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => 'Maison KYC',
            'pays' => 'CD',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $organizationId = $created->json('data.organisation.id');

        $opened = $this->postJson('/api/v1/identity-access/kyc/dossiers', [
            'niveau' => 'organisation_verifiee',
            'organisation_id' => $organizationId,
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated()
            ->assertJsonPath('data.dossier.sujet.type', 'organisation');

        $dossierId = $opened->json('data.dossier.id');
        $this->post("/api/v1/identity-access/kyc/dossiers/{$dossierId}/pieces", [
            'type_doc' => 'identite_representant',
            'fichier' => $this->pdfUpload(),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $this->postJson("/api/v1/identity-access/kyc/dossiers/{$dossierId}/soumission", [], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('data.statut', 'submitted');
    }

    public function test_unknown_level_is_rejected(): void
    {
        $this->activatedAccount();
        $this->postJson('/api/v1/identity-access/kyc/dossiers', [
            'niveau' => 'invente',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'niveau_inconnu');
    }

    private function seedCommercialRequirements(): void
    {
        $this->seedVerificationRequirements([
            ['code' => 'identite_personne', 'volet' => 'identite', 'type_sujet' => 'particulier', 'niveau' => 'partenaire_commercial'],
            ['code' => 'profil_fiscal', 'volet' => 'fiscal', 'type_sujet' => 'particulier', 'niveau' => 'partenaire_commercial'],
            ['code' => 'compte_reversement', 'volet' => 'reversement', 'type_sujet' => 'particulier', 'niveau' => 'partenaire_commercial'],
        ]);
    }

    private function pdfUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('identite.pdf', "%PDF-1.1\n1 0 obj<<>>endobj\ntrailer<>\n%%EOF");
    }
}
