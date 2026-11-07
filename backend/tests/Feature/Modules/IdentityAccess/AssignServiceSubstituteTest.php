<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceSubstitute;
use Illuminate\Support\Str;

class AssignServiceSubstituteTest extends IdentityAccessTestCase
{
    public function test_records_a_substitute_without_granting_a_permission(): void
    {
        $holder = $this->activatedAccount();
        $substitute = $this->activatedAccount();
        $actor = $this->activatedAccount();
        $this->grantInternalPermissions($actor, ['affecter_taches']);

        $key = (string) Str::uuid();
        $payload = [
            'titulaire_id' => $holder->id,
            'suppleant_id' => $substitute->id,
            'fonction' => 'kyc',
            'debut_a' => now()->toIso8601String(),
            'fin_a' => now()->addDays(7)->toIso8601String(),
            'motif' => 'Absence planifiee du titulaire KYC.',
        ];

        $this->postJson('/api/v1/backoffice/identity-access/suppleances', $payload, [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'enregistree')
            ->assertJsonPath('data.suppleance.fonction', 'kyc')
            ->assertJsonPath('data.suppleance.permission_implicite', false)
            ->assertJsonPath('data.suppleance.etat', 'en_vigueur');

        $this->postJson('/api/v1/backoffice/identity-access/suppleances', $payload, [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.suppleance.fonction', 'kyc');

        $this->assertSame(1, ServiceSubstitute::query()->where('titulaire_id', $holder->id)->count());
        $this->assertSame(0, InternalAttribution::query()->where('compte_id', $substitute->id)->count());
        $this->assertFalse((new HasInternalPermission)($substitute->fresh(), 'decider_kyc'));
        $this->assertFalse((new HasInternalPermission)($substitute->fresh(), 'controler_kyc'));
        $this->assertFalse((new HasInternalPermission)($substitute->fresh(), 'affecter_taches'));
        $this->assertSame('suppleance_enregistree', OutgoingMessage::query()->where('operation', 'suppleances_service')->value('type'));

        $this->loginAs($substitute->email_normalise);
        $this->postJson('/api/v1/backoffice/identity-access/kyc/'.str_repeat('0', 26).'/decision', [
            'resultat' => 'approved',
            'motif' => 'Decision par suppleance.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');

        $this->loginAs($actor->email_normalise);
        $this->getJson('/api/v1/backoffice/identity-access/suppleances')
            ->assertOk()
            ->assertJsonPath('data.suppleances.0.titulaire_id', $holder->id);
    }

    public function test_overlap_and_same_account_are_refused(): void
    {
        $holder = $this->activatedAccount();
        $first = $this->activatedAccount();
        $second = $this->activatedAccount();
        $actor = $this->activatedAccount();
        $this->grantInternalPermissions($actor, ['affecter_taches']);

        $this->postJson('/api/v1/backoffice/identity-access/suppleances', [
            'titulaire_id' => $holder->id,
            'suppleant_id' => $first->id,
            'fonction' => 'support',
            'debut_a' => now()->toIso8601String(),
            'fin_a' => now()->addDays(3)->toIso8601String(),
            'motif' => 'Premiere suppleance support.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $this->postJson('/api/v1/backoffice/identity-access/suppleances', [
            'titulaire_id' => $holder->id,
            'suppleant_id' => $second->id,
            'fonction' => 'support',
            'debut_a' => now()->addDay()->toIso8601String(),
            'fin_a' => now()->addDays(4)->toIso8601String(),
            'motif' => 'Chevauchement refuse.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');

        $this->postJson('/api/v1/backoffice/identity-access/suppleances', [
            'titulaire_id' => $holder->id,
            'suppleant_id' => $holder->id,
            'fonction' => 'kyc',
            'debut_a' => now()->toIso8601String(),
            'fin_a' => now()->addDay()->toIso8601String(),
            'motif' => 'Titulaire et suppleant identiques.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'saisie_invalide');
    }

    public function test_missing_permission_is_forbidden(): void
    {
        $holder = $this->activatedAccount();
        $substitute = $this->activatedAccount();
        $this->activatedAccount();

        $this->postJson('/api/v1/backoffice/identity-access/suppleances', [
            'titulaire_id' => $holder->id,
            'suppleant_id' => $substitute->id,
            'fonction' => 'kyc',
            'debut_a' => now()->toIso8601String(),
            'fin_a' => now()->addDay()->toIso8601String(),
            'motif' => 'Sans habilitation d affectation.',
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }
}
