<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceConfiguration;
use Illuminate\Support\Str;

class ApproveServiceConfigurationTest extends IdentityAccessTestCase
{
    public function test_approves_a_new_version_without_replacing_the_current_effect(): void
    {
        $this->seedServicePolicies();
        $actor = $this->activatedAccount();
        $this->grantInternalPermissions($actor, ['configurer_service']);

        $current = ServiceConfiguration::query()->where('code', 'A1_delais')->orderByDesc('version')->first();
        $this->assertNotNull($current);

        $effectAt = now()->addHour()->toIso8601String();
        $key = (string) Str::uuid();
        $payload = [
            'code' => 'A1_delais',
            'valeurs' => [
                'activation_heures' => 48,
                'reset_minutes' => 20,
                'prise_heures_ouvre' => 8,
                'reponse_heures_ouvre' => 40,
            ],
            'effet_a' => $effectAt,
        ];

        $this->postJson('/api/v1/backoffice/identity-access/configurations', $payload, [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'approuvee')
            ->assertJsonPath('data.configuration.code', 'A1_delais')
            ->assertJsonPath('data.configuration.version', $current->version + 1)
            ->assertJsonPath('data.courante', false);

        $this->postJson('/api/v1/backoffice/identity-access/configurations', $payload, [
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.configuration.version', $current->version + 1);

        $this->assertSame($current->id, ServiceConfiguration::query()->where('code', 'A1_delais')->whereNotNull('effet_a')->where('effet_a', '<=', now())->orderByDesc('effet_a')->value('id'));
        $this->assertSame('configuration_approuvee', OutgoingMessage::query()->where('operation', 'configurations_service')->value('type'));

        $this->getJson('/api/v1/backoffice/identity-access/configurations?code=A1_delais')
            ->assertOk()
            ->assertJsonPath('data.configurations.0.code', 'A1_delais')
            ->assertJsonPath('data.configurations.0.courante.id', $current->id);
    }

    public function test_rejects_unknown_code_secret_values_and_missing_permission(): void
    {
        $actor = $this->activatedAccount();
        $this->grantInternalPermissions($actor, ['configurer_service']);

        $this->postJson('/api/v1/backoffice/identity-access/configurations', [
            'code' => 'A1_invente',
            'valeurs' => ['activation_heures' => 24],
            'effet_a' => now()->addHour()->toIso8601String(),
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'saisie_invalide');

        $this->postJson('/api/v1/backoffice/identity-access/configurations', [
            'code' => 'A1_acces',
            'valeurs' => [
                'inactivite_secondes_normal' => 3600,
                'absolue_secondes_normal' => 43200,
                'inactivite_secondes_limited' => 900,
                'absolue_secondes_limited' => 3600,
                'api_token' => 'secret',
            ],
            'effet_a' => now()->addHour()->toIso8601String(),
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'saisie_invalide');

        $other = $this->activatedAccount();
        $this->postJson('/api/v1/backoffice/identity-access/configurations', [
            'code' => 'A1_acces',
            'valeurs' => [
                'inactivite_secondes_normal' => 3600,
                'absolue_secondes_normal' => 43200,
                'inactivite_secondes_limited' => 900,
                'absolue_secondes_limited' => 3600,
            ],
            'effet_a' => now()->addHour()->toIso8601String(),
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'permission_insuffisante');
    }

    public function test_same_effect_timestamp_conflicts(): void
    {
        $actor = $this->activatedAccount();
        $this->grantInternalPermissions($actor, ['configurer_service']);
        $effectAt = now()->addMinutes(30)->toIso8601String();

        $this->postJson('/api/v1/backoffice/identity-access/configurations', [
            'code' => 'A1_acces',
            'valeurs' => [
                'inactivite_secondes_normal' => 1800,
                'absolue_secondes_normal' => 21600,
                'inactivite_secondes_limited' => 600,
                'absolue_secondes_limited' => 1800,
            ],
            'effet_a' => $effectAt,
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();

        $this->postJson('/api/v1/backoffice/identity-access/configurations', [
            'code' => 'A1_acces',
            'valeurs' => [
                'inactivite_secondes_normal' => 2400,
                'absolue_secondes_normal' => 21600,
                'inactivite_secondes_limited' => 600,
                'absolue_secondes_limited' => 1800,
            ],
            'effet_a' => $effectAt,
        ], [
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'etat_incompatible');
    }
}
