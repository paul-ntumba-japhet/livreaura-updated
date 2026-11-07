<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountRole;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\CatalogAuthor;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Role;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use Illuminate\Support\Str;

class EnableAuthorPathTest extends IdentityAccessTestCase
{
    public function test_guest_cannot_enable_author_path(): void
    {
        $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [], [
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

        $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'portee_insuffisante');
    }

    public function test_active_account_receives_author_role_and_draft_fiche(): void
    {
        $account = $this->activatedAccount();
        $key = (string) Str::uuid();

        $first = $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'active')
            ->assertJsonPath('data.prochaine_action', 'compte')
            ->assertJsonPath('data.fiche.statut_publication', 'draft')
            ->assertJsonPath('data.demande', null);

        $ficheId = $first->json('data.fiche.id');
        $this->assertTrue(IdentityUlid::isValid($ficheId));

        $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.fiche.id', $ficheId);

        $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('data.fiche.id', $ficheId);

        $this->assertSame(1, CatalogAuthor::query()->where('compte_id', $account->id)->count());
        $this->assertSame('draft', CatalogAuthor::query()->where('compte_id', $account->id)->value('statut_publication'));
        $this->assertSame(1, AccountEvent::query()->where('compte_sujet_id', $account->id)->where('type', 'auteur_compte_lie')->count());

        $author = Role::query()->where('code', 'author')->first();
        $this->assertNotNull($author);
        $this->assertSame(1, AccountRole::query()->where('compte_id', $account->id)->where('role_id', $author->id)->where('statut', 'active')->count());

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.parcours_auteur.etat', 'active')
            ->assertJsonPath('data.parcours_auteur.fiche.id', $ficheId)
            ->assertJsonPath('data.parcours_auteur.fiche.statut_publication', 'draft');
    }

    public function test_conflicting_idempotency_is_rejected(): void
    {
        $this->activatedAccount();
        $key = (string) Str::uuid();

        $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [], [
            'Idempotency-Key' => $key,
        ])->assertOk();

        $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [
            'auteur_id' => IdentityUlid::make(),
        ], [
            'Idempotency-Key' => $key,
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'intention_conflictuelle');
    }

    public function test_unknown_fiche_is_rejected_without_attachment(): void
    {
        $account = $this->activatedAccount();

        $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [
            'auteur_id' => IdentityUlid::make(),
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'fiche_inconnue');

        $this->assertSame(0, CatalogAuthor::query()->where('compte_id', $account->id)->count());
        $this->assertSame(0, SupportRequest::query()->where('demandeur_compte_id', $account->id)->count());
    }

    public function test_claim_does_not_attach_an_unlinked_or_foreign_fiche(): void
    {
        $account = $this->activatedAccount();
        $unlinked = $this->insertCatalogAuthor();
        $other = Account::factory()->active()->create();
        $foreign = $this->insertCatalogAuthor($other->id, 'published');
        $key = (string) Str::uuid();

        $first = $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [
            'auteur_id' => $unlinked->auteur_id,
        ], [
            'Idempotency-Key' => $key,
        ])->assertStatus(202)
            ->assertJsonPath('data.statut', 'examen')
            ->assertJsonPath('data.prochaine_action', 'attente_examen');

        $reference = $first->json('data.demande.reference');
        $this->assertNotEmpty($reference);

        $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [
            'auteur_id' => $unlinked->auteur_id,
        ], [
            'Idempotency-Key' => $key,
        ])->assertStatus(202)
            ->assertJsonPath('data.demande.reference', $reference);

        $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [
            'auteur_id' => $foreign->auteur_id,
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(202);

        $unlinked->refresh();
        $foreign->refresh();
        $this->assertNull($unlinked->compte_id);
        $this->assertSame($other->id, $foreign->compte_id);
        $this->assertSame('published', $foreign->statut_publication);
        $this->assertSame(0, CatalogAuthor::query()->where('compte_id', $account->id)->count());

        $this->assertSame(2, SupportRequest::query()->where('demandeur_compte_id', $account->id)->where('type', 'revendication_auteur')->count());
        $demandIds = SupportRequest::query()->where('demandeur_compte_id', $account->id)->pluck('id');
        $this->assertSame(2, ServiceTask::query()->whereIn('demande_support_id', $demandIds)->where('etat_travail', 'a_affecter')->count());

        $author = Role::query()->where('code', 'author')->first();
        $this->assertNotNull($author);
        $this->assertSame(0, AccountRole::query()->where('compte_id', $account->id)->where('role_id', $author->id)->count());

        $this->getJson('/api/v1/identity-access/moi')
            ->assertOk()
            ->assertJsonPath('data.parcours_auteur.etat', 'revendication_en_examen')
            ->assertJsonPath('data.parcours_auteur.fiche', null);
    }

    public function test_claiming_own_fiche_enables_the_path(): void
    {
        $account = $this->activatedAccount();
        $fiche = $this->insertCatalogAuthor($account->id, 'draft');

        $this->postJson('/api/v1/identity-access/moi/parcours-auteur', [
            'auteur_id' => $fiche->auteur_id,
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('data.statut', 'active')
            ->assertJsonPath('data.fiche.id', $fiche->auteur_id)
            ->assertJsonPath('data.fiche.statut_publication', 'draft');

        $fiche->refresh();
        $this->assertSame('draft', $fiche->statut_publication);
        $this->assertSame($account->id, $fiche->compte_id);
    }

    private function insertCatalogAuthor(?string $compteId = null, string $status = 'draft'): CatalogAuthor
    {
        $id = IdentityUlid::make();
        $fiche = new CatalogAuthor;
        $fiche->auteur_id = $id;
        $fiche->compte_id = $compteId;
        $fiche->slug = 'auteur-test-'.strtolower(substr($id, -12));
        $fiche->nom_affiche = 'Fiche existante';
        $fiche->verified = 0;
        $fiche->mis_en_avant = 0;
        $fiche->actif = 1;
        $fiche->statut_publication = $status;
        $fiche->save();

        return $fiche;
    }
}
