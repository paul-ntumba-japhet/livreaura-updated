<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use Illuminate\Support\Str;

class OpenAccountClosureTest extends IdentityAccessTestCase
{
    public function test_public_deposit_is_accepted_without_revealing_a_account(): void
    {
        $this->seedServicePolicies();
        $key = (string) Str::uuid();

        $first = $this->postJson('/api/v1/identity-access/cloture', [
            'email' => 'inconnu.cloture@example.test',
            'message' => 'Je demande la fermeture definitive de ce compte.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertStatus(202)
            ->assertJsonPath('data.statut', 'recu')
            ->assertJsonPath('data.prochaine_action', 'conserver_la_reference');

        $reference = $first->json('data.reference');
        $this->assertNotSame('', $reference);
        $this->assertStringStartsWith('CL-', (string) $reference);
        $this->assertNull($first->json('data.compte'));
        $this->assertNull($first->json('data.session'));

        $this->postJson('/api/v1/identity-access/cloture', [
            'email' => 'inconnu.cloture@example.test',
            'message' => 'Je demande la fermeture definitive de ce compte.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertStatus(202)
            ->assertJsonPath('data.reference', $reference);

        $this->assertSame(1, SupportRequest::query()->where('reference', $reference)->where('type', 'cloture')->count());
        $this->assertSame('non_verifiee', SupportRequest::query()->where('reference', $reference)->value('identite_etat'));
        $this->assertNull(SupportRequest::query()->where('reference', $reference)->value('compte_concerne_id'));
        $this->assertSame('demande_cloture_ouverte', OutgoingMessage::query()->where('operation', 'cloture_demande')->value('type'));
    }

    public function test_agent_verifies_identity_without_opening_a_support_session(): void
    {
        $this->seedServicePolicies();
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['traiter_support', 'cloturer_compte']);

        $opened = $this->postJson('/api/v1/identity-access/cloture', [
            'email' => $target->email_normalise,
            'message' => 'Je demande la fermeture de mon compte.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(202);

        $reference = $opened->json('data.reference');
        $this->assertNotNull($reference);

        $this->loginAs($reviewer->email_normalise);
        $key = (string) Str::uuid();
        $verified = $this->postJson("/api/v1/backoffice/identity-access/cloture/{$reference}/identite", [
            'compte_id' => $target->id,
            'motif' => 'Identite du titulaire verifiee sur pieces.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'identite_verifiee')
            ->assertJsonPath('data.prochaine_action', 'cloture_possible')
            ->assertJsonPath('data.demande.identite_etat', 'verifiee')
            ->assertJsonMissingPath('data.session');

        $this->postJson("/api/v1/backoffice/identity-access/cloture/{$reference}/identite", [
            'compte_id' => $target->id,
            'motif' => 'Identite du titulaire verifiee sur pieces.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.demande.reference', $reference);

        $this->assertSame('verifiee', SupportRequest::query()->where('reference', $reference)->value('identite_etat'));
        $this->assertSame($target->id, SupportRequest::query()->where('reference', $reference)->value('compte_concerne_id'));
        $this->assertSame(0, AuthenticationSession::query()->where('demande_support_id', SupportRequest::query()->where('reference', $reference)->value('id'))->count());
        $this->assertSame(0, AuthenticationSession::query()->where('compte_id', $target->id)->where('portee', SessionAccessScope::SupportOnly)->count());

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/cloture", [
            'demande_id' => $reference,
            'motif' => 'Cloture apres demande verifiee.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('data.statut', 'cloture')
            ->assertJsonPath('data.compte.statut', 'closed');

        $this->assertSame(AccountStatus::Closed, $target->fresh()?->statut);
        $this->assertNotNull($verified->json('correlation_id'));
    }

    public function test_identity_verification_requires_support_permission_and_rejects_self(): void
    {
        $this->seedServicePolicies();
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['cloturer_compte']);

        $opened = $this->postJson('/api/v1/identity-access/cloture', [
            'email' => $target->email_normalise,
            'message' => 'Je demande la fermeture de mon compte.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(202);

        $reference = $opened->json('data.reference');

        $this->loginAs($reviewer->email_normalise);
        $this->postJson("/api/v1/backoffice/identity-access/cloture/{$reference}/identite", [
            'compte_id' => $target->id,
            'motif' => 'Tentative sans attribution de support.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');

        $this->grantInternalPermissions($reviewer, ['traiter_support']);
        $this->postJson("/api/v1/backoffice/identity-access/cloture/{$reference}/identite", [
            'compte_id' => $reviewer->id,
            'motif' => 'Auto-verification interdite.',
            'methode_verification' => 'pieces',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }
}
