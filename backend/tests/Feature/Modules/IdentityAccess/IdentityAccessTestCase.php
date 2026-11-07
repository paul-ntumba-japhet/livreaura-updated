<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Application\Queries\ListRegistrationPaths;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Notifications\SimulationIdentityNotificationSender;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\LegalTextVersion;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OutgoingMessage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationType;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceConfiguration;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class IdentityAccessTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->withHeader('Accept', 'application/json');
        $this->withHeader('Origin', 'http://localhost:3000');
        \Illuminate\Support\Carbon::setTestNow();
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Carbon::setTestNow();

        parent::tearDown();
    }

    protected function requiredLegalText(): LegalTextVersion
    {
        $text = new LegalTextVersion;
        $text->id = IdentityUlid::make();
        $text->type = 'cgu';
        $text->version = '1.0-'.strtolower(IdentityUlid::make());
        $text->langue = 'fr';
        $text->contenu = 'Conditions generales de test.';
        $text->reference_immuable = null;
        $text->empreinte = hash('sha256', 'cgu-test-'.$text->id);
        $text->publie_a = now()->subMinutes(2);
        $text->requis_pour_activation = 1;
        $text->save();

        return $text;
    }

    protected function publishedLegalText(string $type, bool $required = true): LegalTextVersion
    {
        $text = new LegalTextVersion;
        $text->id = IdentityUlid::make();
        $text->type = $type;
        $text->version = '1.0-'.strtolower(IdentityUlid::make());
        $text->langue = 'fr';
        $text->contenu = 'Texte legal de test '.$type.'.';
        $text->reference_immuable = 'urn:livreaura:legal:'.$text->id;
        $text->empreinte = hash('sha256', (string) $text->contenu);
        $text->publie_a = now();
        $text->requis_pour_activation = $required ? 1 : 0;
        $text->save();

        return $text;
    }

    protected function loginAs(string $email, string $password = 'password12'): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $email,
            'password' => $password,
        ])->assertOk();
    }

    protected function seedServicePolicies(): void
    {
        $author = Account::factory()->active()->create();

        if (! ServiceConfiguration::query()->where('code', 'A1_acces')->exists()) {
            $this->insertServiceConfiguration('A1_acces', [
                'inactivite_secondes_normal' => 3600,
                'absolue_secondes_normal' => 43200,
                'inactivite_secondes_limited' => 900,
                'absolue_secondes_limited' => 3600,
            ], $author->id);
        }

        if (! ServiceConfiguration::query()->where('code', 'A1_delais')->exists()) {
            $this->insertServiceConfiguration('A1_delais', [
                'activation_heures' => 24,
                'reset_minutes' => 15,
                'prise_heures_ouvre' => 8,
                'reponse_heures_ouvre' => 40,
            ], $author->id, now()->subSeconds(2));
        }

        if (! ServiceConfiguration::query()->where('code', 'A1_calendrier')->exists()) {
            $this->insertServiceConfiguration('A1_calendrier', [
                'timezone' => 'Africa/Kinshasa',
                'plages' => [
                    ['jours' => [1, 2, 3, 4, 5], 'debut' => '09:00', 'fin' => '17:00'],
                ],
                'jours_non_travailles' => [],
            ], $author->id, now()->subSeconds(3));
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function insertServiceConfiguration(string $code, array $values, string $authorId, $effectAt = null): ServiceConfiguration
    {
        $config = new ServiceConfiguration;
        $config->id = IdentityUlid::make();
        $config->code = $code;
        $config->version = 1;
        $config->schema_version = 1;
        $config->valeurs = $values;
        $config->empreinte = hash('sha256', (string) json_encode($values));
        $config->cree_par = $authorId;
        $config->cree_a = now()->subMinute();
        $config->approuve_par = $authorId;
        $config->approuve_a = now()->subMinute();
        $config->effet_a = $effectAt ?? now()->subMinute();
        $config->save();

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    protected function registrationPayload(LegalTextVersion $text, string $email, string $password = 'password12'): array
    {
        return [
            'parcours' => 'lecteur',
            'email' => $email,
            'password' => $password,
            'locale' => 'fr',
            'prenom' => 'Amina',
            'nom' => 'Kabila',
            'pays' => 'CD',
            'ville' => 'Kinshasa',
            'consentements' => [
                [
                    'texte_version_id' => $text->id,
                    'nature' => 'acceptation',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function registrationPayloadForRequiredTexts(string $email, string $password = 'password12'): array
    {
        $required = $this->app->make(ListRegistrationPaths::class)->applicableRequired('fr');

        if ($required->isEmpty()) {
            $required = collect([$this->requiredLegalText()]);
        }

        $payload = $this->registrationPayload($required->first(), $email, $password);
        $payload['consentements'] = $required
            ->map(fn (LegalTextVersion $text): array => [
                'texte_version_id' => $text->id,
                'nature' => 'acceptation',
            ])
            ->all();

        return $payload;
    }

    protected function notifications(): SimulationIdentityNotificationSender
    {
        return $this->app->make(SimulationIdentityNotificationSender::class);
    }

    /**
     * @return array{challenge_id: string, secret: string, transmission_id: string}
     */
    protected function issuedChallengeProof(string $accountId, string $type = 'email_activation'): array
    {
        $messageType = match ($type) {
            'password_reset' => 'defi_password_reset',
            default => 'defi_email_activation',
        };

        $message = OutgoingMessage::query()
            ->where('compte_id', $accountId)
            ->where('type', $messageType)
            ->orderByDesc('cree_a')
            ->first();

        $this->assertNotNull($message);
        $this->assertNotNull($message->defi_id);

        $transmission = MessageTransmission::query()
            ->where('message_id', $message->id)
            ->first();

        $this->assertNotNull($transmission);
        $this->assertNotNull($transmission->contenu_chiffre_temporaire);

        return [
            'challenge_id' => $message->defi_id,
            'secret' => Crypt::decryptString($transmission->contenu_chiffre_temporaire),
            'transmission_id' => $transmission->id,
        ];
    }

    protected function dispatchDueTransmissions(): void
    {
        $this->artisan('a1:dispatch-transmissions')->assertSuccessful();
    }

    protected function uniqueEmail(): string
    {
        return 'inscription.'.strtolower(IdentityUlid::make()).'@example.test';
    }

    protected function seedOrganizationType(string $code = 'editeur', string $label = 'Editeur'): OrganizationType
    {
        $existing = OrganizationType::query()->where('code', $code)->first();

        if ($existing !== null) {
            return $existing;
        }

        $type = new OrganizationType;
        $type->id = IdentityUlid::make();
        $type->code = $code;
        $type->libelle = $label;
        $type->save();

        return $type;
    }

    protected function activatedAccount(): Account
    {
        $this->seedServicePolicies();
        $this->requiredLegalText();
        $email = $this->uniqueEmail();

        $this->postJson(
            '/api/v1/identity-access/inscriptions',
            $this->registrationPayloadForRequiredTexts($email),
            ['Idempotency-Key' => (string) Str::uuid()],
        )->assertCreated();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $proof = $this->issuedChallengeProof($account->id);

        $this->postJson('/api/v1/identity-access/activation', [
            'defi_id' => $proof['challenge_id'],
            'preuve' => $proof['secret'],
        ])->assertOk();

        $this->loginAs($email);

        return $account->fresh() ?? $account;
    }

    /**
     * @param  list<string>  $codes
     */
    protected function grantInternalPermissions(Account $account, array $codes): void
    {
        foreach ($codes as $code) {
            $permission = \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalPermission::query()
                ->where('code', $code)
                ->first();
            $this->assertNotNull($permission);

            $attribution = new \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
            $attribution->id = IdentityUlid::make();
            $attribution->compte_id = $account->id;
            $attribution->permission_id = $permission->id;
            $attribution->attribue_par = $account->id;
            $attribution->attribue_a = now();
            $attribution->motif = 'Attribution de test.';
            $attribution->save();
        }
    }

    /**
     * @param  list<array{code: string, volet: string, type_sujet: string, niveau: string, obligatoire?: bool}>  $definitions
     */
    protected function seedVerificationRequirements(array $definitions, string $country = 'CD'): void
    {
        foreach ($definitions as $definition) {
            $requirement = new \App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationRequirement;
            $requirement->id = IdentityUlid::make();
            $requirement->code = $definition['code'];
            $requirement->version_regles = '1.0-'.strtolower(IdentityUlid::make());
            $requirement->type_sujet = $definition['type_sujet'];
            $requirement->pays = $country;
            $requirement->niveau = $definition['niveau'];
            $requirement->volet = $definition['volet'];
            $requirement->nature_preuve = $definition['code'];
            $requirement->obligatoire = ($definition['obligatoire'] ?? true) ? 1 : 0;
            $requirement->autorise_non_applicable = 0;
            $requirement->date_effet = now()->subDay();
            $requirement->save();
        }
    }
}
