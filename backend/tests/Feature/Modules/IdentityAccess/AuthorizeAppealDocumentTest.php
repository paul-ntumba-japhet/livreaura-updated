<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportDocumentGrant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthorizeAppealDocumentTest extends IdentityAccessTestCase
{
    public function test_agent_authorizes_an_owned_sale_invoice_grant_and_replay_is_idempotent(): void
    {
        [$target, $reference] = $this->suspendedAccountWithVerifiedAppeal();
        $invoiceId = $this->insertOwnedSaleInvoice($target->id);
        $key = (string) Str::uuid();
        $starts = now()->toIso8601String();
        $ends = now()->addDays(2)->toIso8601String();

        $first = $this->postJson("/api/v1/backoffice/identity-access/recours/{$reference}/acces-documents", [
            'compte_id' => $target->id,
            'facture_vente_id' => $invoiceId,
            'debut' => $starts,
            'fin' => $ends,
            'motif' => 'Facture de la commande du titulaire.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'grant_autorise')
            ->assertJsonPath('data.prochaine_action', 'consultation_documentaire')
            ->assertJsonPath('data.acces.document.type', 'facture_vente')
            ->assertJsonPath('data.acces.document.id', $invoiceId)
            ->assertJsonMissingPath('data.acces.url');

        $this->postJson("/api/v1/backoffice/identity-access/recours/{$reference}/acces-documents", [
            'compte_id' => $target->id,
            'facture_vente_id' => $invoiceId,
            'debut' => $starts,
            'fin' => $ends,
            'motif' => 'Facture de la commande du titulaire.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.acces.id', $first->json('data.acces.id'));

        $this->assertSame(1, SupportDocumentGrant::query()->where('compte_id', $target->id)->count());
        $this->assertSame('acces_document_modifie', OutgoingMessage::query()->where('operation', 'A1-26')->value('type'));
        $this->assertNotSame('', $first->json('correlation_id'));
    }

    public function test_xor_rejects_two_document_references(): void
    {
        [$target, $reference] = $this->suspendedAccountWithVerifiedAppeal();

        $this->postJson("/api/v1/backoffice/identity-access/recours/{$reference}/acces-documents", [
            'compte_id' => $target->id,
            'facture_vente_id' => IdentityUlid::make(),
            'avoir_id' => IdentityUlid::make(),
            'debut' => now()->toIso8601String(),
            'fin' => now()->addDay()->toIso8601String(),
            'motif' => 'Deux documents dans le meme grant.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'saisie_invalide');
    }

    public function test_unknown_document_is_not_visible(): void
    {
        [$target, $reference] = $this->suspendedAccountWithVerifiedAppeal();

        $this->postJson("/api/v1/backoffice/identity-access/recours/{$reference}/acces-documents", [
            'compte_id' => $target->id,
            'facture_vente_id' => IdentityUlid::make(),
            'debut' => now()->toIso8601String(),
            'fin' => now()->addDay()->toIso8601String(),
            'motif' => 'Document commercial introuvable.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertNotFound()
            ->assertJsonPath('error.code', 'document_inconnu');
    }

    public function test_document_of_another_account_is_ineligible(): void
    {
        [$target, $reference] = $this->suspendedAccountWithVerifiedAppeal();
        $invoiceId = $this->insertOwnedSaleInvoice(IdentityUlid::make());

        $this->postJson("/api/v1/backoffice/identity-access/recours/{$reference}/acces-documents", [
            'compte_id' => $target->id,
            'facture_vente_id' => $invoiceId,
            'debut' => now()->toIso8601String(),
            'fin' => now()->addDay()->toIso8601String(),
            'motif' => 'Facture d un autre titulaire.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'document_ineligible');
    }

    public function test_grant_requires_both_support_permissions(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['traiter_support']);

        $this->postJson('/api/v1/backoffice/identity-access/recours/RS-ABCDEFGHIJ/acces-documents', [
            'compte_id' => $target->id,
            'facture_vente_id' => IdentityUlid::make(),
            'debut' => now()->toIso8601String(),
            'fin' => now()->addDay()->toIso8601String(),
            'motif' => 'Sans autoriser_recours.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    public function test_unverified_demand_cannot_receive_a_grant(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte', 'traiter_support', 'autoriser_recours']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/suspension", [
            'motif' => 'Indisponibilite operationnelle du titulaire.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();

        $opened = $this->postJson('/api/v1/identity-access/recours', [
            'email' => $target->email_normalise,
            'message' => 'Recours encore non verifie.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(202);

        $this->loginAs($reviewer->email_normalise);
        $this->postJson('/api/v1/backoffice/identity-access/recours/'.$opened->json('data.reference').'/acces-documents', [
            'compte_id' => $target->id,
            'facture_vente_id' => IdentityUlid::make(),
            'debut' => now()->toIso8601String(),
            'fin' => now()->addDay()->toIso8601String(),
            'motif' => 'Grant avant verification.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }

    /**
     * @return array{0: \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account, 1: string}
     */
    private function suspendedAccountWithVerifiedAppeal(): array
    {
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte', 'traiter_support', 'autoriser_recours']);
        $target = $this->activatedAccount();

        $this->loginAs($reviewer->email_normalise);
        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/suspension", [
            'motif' => 'Indisponibilite operationnelle du titulaire.',
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
        $this->postJson("/api/v1/backoffice/identity-access/recours/{$reference}/autorisation-session", [
            'compte_id' => $target->id,
            'motif' => 'Identite du titulaire verifiee sur pieces.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();

        return [$target, $reference];
    }

    private function insertOwnedSaleInvoice(string $accountId): string
    {
        $id = IdentityUlid::make();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('factures_vente')->insert([
            'id' => $id,
            'dossier_facturation_id' => IdentityUlid::make(),
            'numero' => 'FV-'.substr($id, 10, 8),
            'serie' => 'TEST',
            'emetteur_type' => 'vendeur',
            'emetteur_cle_snap' => 'test-emetteur',
            'identite_vendeur_snap' => json_encode(['nom' => 'Vendeur'], JSON_THROW_ON_ERROR),
            'identite_client_snap' => json_encode(['compte_id' => $accountId], JSON_THROW_ON_ERROR),
            'ht' => 100,
            'taxes' => 0,
            'ttc' => 100,
            'devise' => 'USD',
            'emise_a' => now(),
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return $id;
    }
}
