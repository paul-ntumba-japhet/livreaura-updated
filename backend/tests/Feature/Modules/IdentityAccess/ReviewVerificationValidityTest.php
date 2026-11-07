<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Application\Support\IsCurrentVerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Vendor;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationControl;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationEvent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewVerificationValidityTest extends IdentityAccessTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kyc');
    }

    public function test_review_expiries_expires_an_overdue_commercial_capability_and_deactivates_the_vendor(): void
    {
        $this->activatedAccount();
        $dossierId = $this->submitCommercialDossier();
        $this->approveCommercialDossier($dossierId);

        $capability = VerificationCapability::query()->where('niveau', 'partenaire_commercial')->first();
        $this->assertNotNull($capability);
        $capability->valide_jusqua = now()->subMinute();
        $capability->save();

        $this->assertFalse(($this->app->make(IsCurrentVerificationCapability::class))($capability->fresh()));

        $this->artisan('a1:review-expiries')->assertSuccessful();

        $capability->refresh();
        $this->assertSame('expired', $capability->etat);
        $this->assertSame(0, Vendor::query()->where('actif', 1)->count());
        $this->assertSame(
            'habilitation_commerciale_modifiee',
            OutgoingMessage::query()->where('operation', 'A1-14')->where('evenement_kyc_id', '!=', null)->value('type'),
        );
        $this->assertSame(
            'expiration',
            VerificationEvent::query()
                ->where('habilitation_id', $capability->id)
                ->where('origine_systeme', 'A1-14')
                ->value('type'),
        );

        $this->artisan('a1:review-expiries')->assertSuccessful();
        $this->assertSame(1, OutgoingMessage::query()->where('operation', 'A1-14')->whereNotNull('evenement_kyc_id')->count());
    }

    public function test_reminder_does_not_withdraw_valid(): void
    {
        $this->activatedAccount();
        $dossierId = $this->submitCommercialDossier();
        $this->approveCommercialDossier($dossierId);

        $capability = VerificationCapability::query()->where('niveau', 'partenaire_commercial')->first();
        $this->assertNotNull($capability);
        $capability->valide_jusqua = now()->startOfDay()->addDays(7)->setTime(17, 0);
        $capability->save();

        $this->artisan('a1:send-verification-reminders')->assertSuccessful();

        $capability->refresh();
        $this->assertSame('valid', $capability->etat);
        $this->assertSame(1, Vendor::query()->where('actif', 1)->count());
        $this->assertSame(1, OutgoingMessage::query()->where('operation', 'A1-14')->count());

        $this->artisan('a1:send-verification-reminders')->assertSuccessful();
        $this->assertSame(1, OutgoingMessage::query()->where('operation', 'A1-14')->count());
    }

    public function test_reviewer_can_restrict_a_capability_and_replay_is_idempotent(): void
    {
        $this->activatedAccount();
        $dossierId = $this->submitCommercialDossier();
        $this->approveCommercialDossier($dossierId);

        $capability = VerificationCapability::query()->where('niveau', 'partenaire_commercial')->first();
        $this->assertNotNull($capability);

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['restreindre_habilitation']);

        $this->getJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}")
            ->assertOk()
            ->assertJsonPath('data.capacites.restreindre', true)
            ->assertJsonPath('data.habilitation.etat', 'valid');

        $key = (string) Str::uuid();
        $this->postJson("/api/v1/backoffice/identity-access/habilitations/{$capability->id}/restriction", [
            'etat' => 'restreint',
            'motif' => 'Changement sensible consigne.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.habilitation.etat', 'restreint')
            ->assertJsonPath('data.statut', 'restreint');

        $this->postJson("/api/v1/backoffice/identity-access/habilitations/{$capability->id}/restriction", [
            'etat' => 'restreint',
            'motif' => 'Changement sensible consigne.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.habilitation.id', $capability->id);

        $this->assertSame('restreint', $capability->fresh()->etat);
        $this->assertSame(0, Vendor::query()->where('actif', 1)->count());
        $this->assertSame(1, VerificationEvent::query()->where('type', 'restriction')->where('habilitation_id', $capability->id)->count());
    }

    public function test_restriction_requires_the_internal_permission(): void
    {
        $this->activatedAccount();
        $dossierId = $this->submitCommercialDossier();
        $this->approveCommercialDossier($dossierId);
        $capabilityId = VerificationCapability::query()->where('niveau', 'partenaire_commercial')->value('id');

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['controler_kyc']);

        $this->postJson("/api/v1/backoffice/identity-access/habilitations/{$capabilityId}/restriction", [
            'etat' => 'revoked',
            'motif' => 'Sans attribution de restriction.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    public function test_expired_capability_cannot_be_restored_by_restriction_and_a_new_version_can_be_opened(): void
    {
        $depositor = $this->activatedAccount();
        $dossierId = $this->submitCommercialDossier();
        $this->approveCommercialDossier($dossierId);

        $capability = VerificationCapability::query()->where('niveau', 'partenaire_commercial')->first();
        $this->assertNotNull($capability);
        $capability->valide_jusqua = now()->subMinute();
        $capability->save();
        $this->artisan('a1:review-expiries')->assertSuccessful();

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['restreindre_habilitation']);
        $this->postJson("/api/v1/backoffice/identity-access/habilitations/{$capability->id}/restriction", [
            'etat' => 'a_renouveler',
            'motif' => 'Tentative de retablissement.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');

        $this->loginAs($depositor->email_normalise);
        $opened = $this->postJson('/api/v1/identity-access/kyc/dossiers', [
            'niveau' => 'partenaire_commercial',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $this->assertNotSame($dossierId, $opened->json('data.dossier.id'));
        $this->assertSame('draft', $opened->json('data.dossier.statut'));
        $this->assertSame(1, $opened->json('data.dossier.version'));
    }

    private function approveCommercialDossier(string $dossierId): void
    {
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['affecter_taches', 'controler_kyc', 'decider_kyc', 'decider_kyc_commercial']);
        $taskId = ServiceTask::query()->where('dossier_kyc_id', $dossierId)->value('id');
        $this->postJson("/api/v1/backoffice/identity-access/taches/{$taskId}/affectation")->assertOk();
        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/prise-en-charge")->assertOk();
        $controlIds = VerificationControl::query()->where('dossier_id', $dossierId)->pluck('id');
        $this->assertNotEmpty($controlIds);
        foreach ($controlIds as $controlId) {
            $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/controles", [
                'controle_id' => $controlId,
                'resultat' => 'passed',
                'observation' => 'Controle consigne.',
            ])->assertOk();
        }
        $this->postJson("/api/v1/backoffice/identity-access/kyc/{$dossierId}/decision-commerciale", [
            'resultat' => 'approved',
            'motif' => 'Identite, fiscal et reversement controles.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();
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
                'identifiant_fiscal' => 'IF-014',
            ],
            'compte_reversement' => [
                'canal' => 'virement',
                'titulaire' => 'Amina Kabila',
                'pays' => 'CD',
                'devise' => 'USD',
                'reference_prestataire' => 'REF-PAY-014',
                'affichage_masque' => 'Compte se terminant par 014',
            ],
        ])->assertOk();

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
