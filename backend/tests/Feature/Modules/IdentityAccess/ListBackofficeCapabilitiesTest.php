<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\KycSubject;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDecision;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;
use Illuminate\Support\Str;

class ListBackofficeCapabilitiesTest extends IdentityAccessTestCase
{
    public function test_lists_verification_capabilities(): void
    {
        $owner = $this->activatedAccount();
        $capabilityId = $this->seedOrganizationCapability($owner->id, 'Maison habilitation liste', 'valid', now()->addYear());

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['controler_kyc']);

        $response = $this->getJson('/api/v1/backoffice/identity-access/habilitations?per_page=50')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'toutes')
            ->assertJsonPath('data.pagination.per_page', 50);

        $this->getJson('/api/v1/backoffice/identity-access/habilitations')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 6);

        $payload = (string) json_encode($response->json('data'));
        $this->assertStringNotContainsString('hash_mdp', $payload);

        $row = collect($response->json('data.habilitations'))->firstWhere('id', $capabilityId);
        $this->assertIsArray($row);
        $this->assertSame('organisation_verifiee', $row['niveau']);
        $this->assertSame('valid', $row['etat']);
        $this->assertSame('Maison habilitation liste', $row['beneficiaire']['libelle'] ?? null);
        $this->assertSame('organisation', $row['beneficiaire']['type'] ?? null);
        $this->assertGreaterThanOrEqual(1, $response->json('data.totaux.valides'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.totaux.toutes'));
    }

    public function test_filters_search_and_tabs(): void
    {
        $owner = $this->activatedAccount();
        $this->seedOrganizationCapability($owner->id, 'Maison habilitation liste', 'valid', now()->addDays(10));

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['restreindre_habilitation']);

        $names = collect($this->getJson('/api/v1/backoffice/identity-access/habilitations?q='.urlencode('Maison habilitation liste'))
            ->assertOk()
            ->json('data.habilitations'))
            ->pluck('beneficiaire.libelle')
            ->all();
        $this->assertContains('Maison habilitation liste', $names);

        $this->getJson('/api/v1/backoffice/identity-access/habilitations?onglet=valides')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'valides');
        $this->getJson('/api/v1/backoffice/identity-access/habilitations?onglet=expirees')
            ->assertOk()
            ->assertJsonPath('data.onglet', 'expirees');
        $this->getJson('/api/v1/backoffice/identity-access/habilitations?echeance=30j')
            ->assertOk();
    }

    public function test_refuses_the_directory_without_permission(): void
    {
        $this->activatedAccount();

        $this->getJson('/api/v1/backoffice/identity-access/habilitations')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    private function seedOrganizationCapability(
        string $depositorId,
        string $raisonSociale,
        string $etat,
        \DateTimeInterface $until,
    ): string {
        $type = $this->seedOrganizationType();
        $created = $this->postJson('/api/v1/identity-access/organisations', [
            'type_organisation_id' => $type->id,
            'raison_sociale' => $raisonSociale,
            'pays' => 'CD',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $organizationId = $created->json('data.organisation.id');
        $subject = new KycSubject;
        $subject->id = IdentityUlid::make();
        $subject->organisation_id = $organizationId;
        $subject->save();

        $dossier = new VerificationDossier;
        $dossier->id = IdentityUlid::make();
        $dossier->sujet_id = $subject->id;
        $dossier->numero = 'KV-'.substr($dossier->id, -10);
        $dossier->version = 1;
        $dossier->niveau = 'organisation_verifiee';
        $dossier->statut = 'approved';
        $dossier->deposant_id = $depositorId;
        $dossier->cree_a = now()->subDay();
        $dossier->soumis_a = now()->subDay();
        $dossier->clos_a = now()->subHour();
        $dossier->save();

        $decision = new VerificationDecision;
        $decision->id = IdentityUlid::make();
        $decision->dossier_id = $dossier->id;
        $decision->decideur_id = $depositorId;
        $decision->resultat = 'approved';
        $decision->motif = 'Habilitation de test.';
        $decision->decide_a = now()->subHour();
        $decision->valide_jusqua = $until;
        $decision->save();

        $capability = new VerificationCapability;
        $capability->id = IdentityUlid::make();
        $capability->sujet_id = $subject->id;
        $capability->niveau = 'organisation_verifiee';
        $capability->dossier_courant_id = $dossier->id;
        $capability->decision_courante_id = $decision->id;
        $capability->etat = $etat;
        $capability->obtenue_a = now()->subHour();
        $capability->valide_jusqua = $until;
        $capability->save();

        return $capability->id;
    }
}
