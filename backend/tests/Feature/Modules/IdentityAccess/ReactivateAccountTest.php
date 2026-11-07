<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceTask;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportExchange;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;
use Illuminate\Support\Str;

class ReactivateAccountTest extends IdentityAccessTestCase
{
    public function test_reviewer_reactivates_a_suspended_account_and_replay_is_idempotent(): void
    {
        [$target, $reviewer, $reference] = $this->suspendedAccountWithVerifiedAppeal('Mesure de securite humaine.');

        $key = (string) Str::uuid();
        $first = $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/reactivation", [
            'demande_id' => $reference,
            'motif' => 'Conditions reunies apres reexamen.',
            'avis_securite' => 'Avis securite favorable du responsable habilite.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.statut', 'reactivation')
            ->assertJsonPath('data.prochaine_action', 'connexion')
            ->assertJsonPath('data.propagation', 'en_cours')
            ->assertJsonPath('data.compte.statut', 'active')
            ->assertJsonPath('data.avis_securite_consigne', true)
            ->assertJsonPath('data.conditions.email_verifie', true)
            ->assertJsonPath('data.conditions.profil_complet', true)
            ->assertJsonPath('data.conditions.textes_requis', true);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/reactivation", [
            'demande_id' => $reference,
            'motif' => 'Conditions reunies apres reexamen.',
            'avis_securite' => 'Avis securite favorable du responsable habilite.',
        ], [
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.compte.id', $target->id)
            ->assertJsonPath('data.statut', 'reactivation');

        $this->assertSame(AccountStatus::Active, $target->fresh()?->statut);
        $this->assertSame(0, VerificationCapability::query()->count());
        $this->assertNotNull(SupportRequest::query()->where('reference', $reference)->value('clos_a'));
        $this->assertSame('terminee', ServiceTask::query()->where('demande_support_id', SupportRequest::query()->where('reference', $reference)->value('id'))->value('etat_travail'));
        $this->assertSame(0, AuthenticationSession::query()->where('compte_id', $target->id)->where('statut', SessionStatus::Active)->count());
        $this->assertGreaterThan(0, AuthenticationSession::query()->where('compte_id', $reviewer->id)->where('statut', SessionStatus::Active)->count());
        $this->assertSame(1, SupportExchange::query()->where('visibilite', 'interne')->where('contenu_prive', 'Avis securite favorable du responsable habilite.')->count());

        $event = AccountEvent::query()
            ->where('compte_sujet_id', $target->id)
            ->where('type', 'compte_acces_modifie')
            ->where('auteur_compte_id', $reviewer->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($event);
        $this->assertNull($event->origine_systeme);
        $this->assertSame('Conditions reunies apres reexamen.', $event->motif);

        $this->assertSame('compte_acces_modifie', OutgoingMessage::query()->where('operation', 'A1-23')->where('cle_idempotence', 'like', 'a1-23:reactivation:%')->value('type'));
        $this->assertNotSame('', $first->json('correlation_id'));

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $target->email_normalise,
            'password' => 'password12',
        ])->assertOk()
            ->assertJsonPath('data.compte.statut', 'active')
            ->assertJsonPath('data.session.portee', SessionAccessScope::Normal->value);
    }

    public function test_incomplete_conditions_reactivate_to_pending(): void
    {
        [$target, $reviewer, $reference] = $this->suspendedAccountWithVerifiedAppeal('Indisponibilite operationnelle du titulaire.');

        $target->email_verifie_a = null;
        $target->save();

        $this->loginAs($reviewer->email_normalise);
        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/reactivation", [
            'demande_id' => $reference,
            'motif' => 'Levee avec conditions encore incompletes.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('data.compte.statut', 'pending')
            ->assertJsonPath('data.conditions.email_verifie', false)
            ->assertJsonPath('data.avis_securite_consigne', false);

        $this->assertSame(AccountStatus::Pending, $target->fresh()?->statut);

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $target->email_normalise,
            'password' => 'password12',
        ])->assertOk()
            ->assertJsonPath('data.compte.statut', 'pending')
            ->assertJsonPath('data.session.portee', SessionAccessScope::Limited->value);
    }

    public function test_security_suspension_requires_recorded_advice(): void
    {
        [$target, $reviewer, $reference] = $this->suspendedAccountWithVerifiedAppeal('Mesure de securite humaine.');

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/reactivation", [
            'demande_id' => $reference,
            'motif' => 'Tentative sans avis de securite.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'saisie_invalide');

        $this->assertSame(AccountStatus::Suspended, $target->fresh()?->statut);
        $this->assertNull(SupportRequest::query()->where('reference', $reference)->value('clos_a'));
        $this->assertGreaterThan(0, AuthenticationSession::query()->where('compte_id', $reviewer->id)->where('statut', SessionStatus::Active)->count());
    }

    public function test_reactivation_requires_the_internal_permission(): void
    {
        [$target, $reviewer, $reference] = $this->suspendedAccountWithVerifiedAppeal('Indisponibilite operationnelle du titulaire.');
        $this->grantInternalPermissions($reviewer, ['controler_kyc']);

        $other = $this->activatedAccount();
        $this->grantInternalPermissions($other, ['controler_kyc']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/reactivation", [
            'demande_id' => $reference,
            'motif' => 'Sans attribution de reactivation.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    public function test_operator_cannot_reactivate_self(): void
    {
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['reactiver_compte']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$reviewer->id}/reactivation", [
            'demande_id' => 'RS-ABCDEFGHIJ',
            'motif' => 'Auto-reactivation interdite.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }

    public function test_unverified_demand_cannot_lift_suspension(): void
    {
        $target = $this->activatedAccount();
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte', 'reactiver_compte']);

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
        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/reactivation", [
            'demande_id' => $opened->json('data.reference'),
            'motif' => 'Levee sans verification d identite.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }

    public function test_closed_account_cannot_be_reactivated(): void
    {
        $target = $this->activatedAccount();
        $target->statut = AccountStatus::Closed;
        $target->ferme_a = now();
        $target->save();

        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['reactiver_compte']);

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/reactivation", [
            'demande_id' => 'RS-ABCDEFGHIJ',
            'motif' => 'Compte deja cloture.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }

    public function test_unknown_account_is_not_visible(): void
    {
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['reactiver_compte']);
        $unknownId = IdentityUlid::make();

        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$unknownId}/reactivation", [
            'demande_id' => 'RS-ABCDEFGHIJ',
            'motif' => 'Compte introuvable pour reactivation.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertNotFound()
            ->assertJsonPath('error.code', 'compte_inconnu');
    }

    /**
     * @return array{0: \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account, 1: \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account, 2: string}
     */
    private function suspendedAccountWithVerifiedAppeal(string $suspensionMotif): array
    {
        $reviewer = $this->activatedAccount();
        $this->grantInternalPermissions($reviewer, ['suspendre_compte', 'traiter_support', 'autoriser_recours', 'reactiver_compte']);
        $target = $this->activatedAccount();

        $this->loginAs($reviewer->email_normalise);
        $this->postJson("/api/v1/backoffice/identity-access/comptes/{$target->id}/suspension", [
            'motif' => $suspensionMotif,
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

        return [$target, $reviewer, $reference];
    }
}
