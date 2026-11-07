<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportEvidence;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportExchange;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ReplyToSupportTest extends IdentityAccessTestCase
{
    public function test_agent_and_claimant_exchange_without_exposing_internal_notes(): void
    {
        [$target, $reviewer, $reference] = $this->openedAuthorizedAppeal();

        $this->loginAs($reviewer->email_normalise);

        $this->getJson('/api/v1/identity-access/support/'.$reference)
            ->assertOk()
            ->assertJsonPath('data.demande.reference', $reference)
            ->assertJsonPath('data.capacites.notes_internes', true);

        $key = (string) Str::uuid();
        $this->postJson('/api/v1/identity-access/support/'.$reference.'/echanges', [
            'contenu' => 'Note interne de qualification du recours.',
            'visibilite' => 'interne',
        ], [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.echange.visibilite', 'interne')
            ->assertJsonPath('data.statut', 'echange_ajoute');

        $this->postJson('/api/v1/identity-access/support/'.$reference.'/echanges', [
            'contenu' => 'Note interne de qualification du recours.',
            'visibilite' => 'interne',
        ], [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.echange.visibilite', 'interne');

        $this->postJson('/api/v1/identity-access/support/'.$reference.'/echanges', [
            'contenu' => 'Merci de preciser la piece d identite deja transmise.',
            'visibilite' => 'demandeur',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated()
            ->assertJsonPath('data.echange.visibilite', 'demandeur');

        $this->assertSame('echange_support_ajoute', OutgoingMessage::query()->where('operation', 'echanges_support')->value('type'));

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $target->email_normalise,
            'password' => 'password12',
        ])->assertOk();

        $claimantView = $this->getJson('/api/v1/identity-access/support/'.$reference)
            ->assertOk()
            ->assertJsonPath('data.capacites.notes_internes', false)
            ->assertJsonPath('data.capacites.repondre', true);

        $visibilities = collect($claimantView->json('data.echanges'))->pluck('visibilite')->unique()->all();
        $this->assertSame(['demandeur'], $visibilities);

        $this->postJson('/api/v1/identity-access/support/'.$reference.'/echanges', [
            'contenu' => 'Voici le complement demande pour le recours.',
            'visibilite' => 'interne',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated()
            ->assertJsonPath('data.echange.origine', 'demandeur')
            ->assertJsonPath('data.echange.visibilite', 'demandeur');

        $this->assertSame(0, SupportExchange::query()->where('demande_id', SupportRequest::query()->where('reference', $reference)->value('id'))->where('origine', 'demandeur')->where('visibilite', 'interne')->count());
    }

    public function test_attaches_a_private_file_and_hides_internal_evidence_from_the_claimant(): void
    {
        [$target, $reviewer, $reference] = $this->openedAuthorizedAppeal();

        $this->loginAs($reviewer->email_normalise);
        $internal = $this->post('/api/v1/identity-access/support/'.$reference.'/pieces', [
            'type' => 'identite',
            'acces' => 'interne',
            'fichier' => $this->pdfUpload(),
        ])->assertCreated();

        $internalId = $internal->json('data.piece.id');
        $this->assertNotNull($internalId);
        $this->assertSame(1, SupportEvidence::query()->where('id', $internalId)->where('acces', 'interne')->count());

        $download = $this->get('/api/v1/identity-access/support/'.$reference.'/pieces/'.$internalId);
        $download->assertOk();
        $this->assertStringContainsString('no-store', (string) $download->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $download->headers->get('Cache-Control'));

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $target->email_normalise,
            'password' => 'password12',
        ])->assertOk();

        $visible = $this->post('/api/v1/identity-access/support/'.$reference.'/pieces', [
            'type' => 'complement',
            'fichier' => $this->pdfUpload('complement.pdf'),
        ])->assertCreated();

        $this->assertSame('demandeur', $visible->json('data.piece.acces'));
        $this->getJson('/api/v1/identity-access/support/'.$reference)
            ->assertOk()
            ->assertJsonMissing(['id' => $internalId]);

        $this->get('/api/v1/identity-access/support/'.$reference.'/pieces/'.$internalId)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'piece_inconnue');
    }

    public function test_stranger_and_closed_demand_are_refused(): void
    {
        [, $reviewer, $reference] = $this->openedAuthorizedAppeal();
        $stranger = $this->activatedAccount();

        $this->postJson('/api/v1/identity-access/support/'.$reference.'/echanges', [
            'contenu' => 'Tentative d acces a une autre demande.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertNotFound()
            ->assertJsonPath('error.code', 'demande_inconnue');

        $this->loginAs($reviewer->email_normalise);
        $demande = SupportRequest::query()->where('reference', $reference)->first();
        $this->assertNotNull($demande);
        $demande->clos_a = now();
        $demande->save();

        $this->postJson('/api/v1/identity-access/support/'.$reference.'/echanges', [
            'contenu' => 'Reponse apres cloture de la demande.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');

        $this->getJson('/api/v1/identity-access/support/'.$reference)
            ->assertOk()
            ->assertJsonPath('data.capacites.repondre', false);
    }

    /**
     * @return array{0: \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account, 1: \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account, 2: string}
     */
    private function openedAuthorizedAppeal(): array
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

        return [$target, $reviewer, $reference];
    }

    private function pdfUpload(string $name = 'identite.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.1\n1 0 obj<<>>endobj\ntrailer<>\n%%EOF");
    }
}
