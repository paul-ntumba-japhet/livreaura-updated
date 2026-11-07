<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Application\Actions\Accounts\BootstrapIdentityInstall;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountRole;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\LegalTextVersion;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationType;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Role;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationRequirement;
use App\Modules\IdentityAccess\Presentation\Console\Commands\BootstrapIdentityInstallCommand;
use Illuminate\Support\Facades\Hash;

class BootstrapInstallTest extends IdentityAccessTestCase
{
    protected function tearDown(): void
    {
        putenv('A1_BOOTSTRAP_PASSWORD');
        unset($_ENV['A1_BOOTSTRAP_PASSWORD']);

        parent::tearDown();
    }

    public function test_command_does_not_accept_a_password_argument(): void
    {
        $definition = $this->app->make(BootstrapIdentityInstallCommand::class)->getDefinition();

        $this->assertFalse($definition->hasOption('password'));
        $this->assertFalse($definition->hasArgument('password'));
    }

    public function test_bootstrap_creates_install_account_texts_and_configurations(): void
    {
        $email = $this->uniqueEmail();
        putenv('A1_BOOTSTRAP_PASSWORD=password12');
        $_ENV['A1_BOOTSTRAP_PASSWORD'] = 'password12';

        $this->artisan('a1:bootstrap-install', [
            '--email' => $email,
            '--prenom' => 'Install',
            '--nom' => 'Livreaura',
            '--pays' => 'CD',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $account = Account::query()->where('email_normalise', $email)->first();
        $this->assertNotNull($account);
        $this->assertSame(AccountStatus::Active, $account->statut);
        $this->assertNotNull($account->email_verifie_a);
        $this->assertTrue(Hash::check('password12', $account->getAuthPassword()));
        $this->assertNotNull($account->profile);

        $admin = Role::query()->where('code', 'admin')->first();
        $reader = Role::query()->where('code', 'reader')->first();
        $this->assertNotNull($admin);
        $this->assertNotNull($reader);
        $this->assertTrue(
            AccountRole::query()->where('compte_id', $account->id)->where('role_id', $admin->id)->where('origine', BootstrapIdentityInstall::ORIGIN)->exists()
        );
        $this->assertTrue(
            AccountRole::query()->where('compte_id', $account->id)->where('role_id', $reader->id)->exists()
        );

        $this->assertSame(2, LegalTextVersion::query()->whereIn('type', ['cgu', 'confidentialite'])->where('version', '1.0')->count());
        $this->assertNotNull(ServiceConfiguration::query()->where('code', 'A1_acces')->whereNotNull('effet_a')->first());
        $this->assertNotNull(ServiceConfiguration::query()->where('code', 'A1_delais')->whereNotNull('effet_a')->first());
        $this->assertSame(15, InternalAttribution::query()->where('compte_id', $account->id)->whereNull('revoque_a')->count());
        $this->assertTrue(
            InternalAttribution::query()
                ->where('compte_id', $account->id)
                ->whereNull('revoque_a')
                ->whereHas('permission', fn ($query) => $query->where('code', 'suspendre_compte'))
                ->exists()
        );
        $this->assertTrue(OrganizationType::query()->where('code', 'ecole')->exists());
        $this->assertTrue(OrganizationType::query()->where('code', 'universite')->exists());
        $this->assertTrue(OrganizationType::query()->where('code', 'bibliotheque')->exists());
        $this->assertTrue(OrganizationType::query()->where('code', 'editeur')->exists());
        $this->assertTrue(OrganizationType::query()->where('code', 'librairie')->exists());
        $this->assertTrue(OrganizationType::query()->where('code', 'association')->exists());
        $this->assertTrue(VerificationRequirement::query()->where('code', 'identite_representant')->exists());
        $this->assertTrue(VerificationRequirement::query()->where('code', 'mandat_representant')->exists());
        $this->assertTrue(VerificationRequirement::query()->where('code', 'existence_ecole')->exists());
        $this->assertTrue(VerificationRequirement::query()->where('code', 'existence_editeur')->exists());

        $this->postJson('/api/v1/identity-access/connexion', [
            'email' => $email,
            'password' => 'password12',
        ])->assertOk()
            ->assertJsonPath('data.compte.statut', 'active')
            ->assertJsonPath('data.session.portee', 'normal');
    }

    public function test_bootstrap_is_idempotent(): void
    {
        $email = $this->uniqueEmail();
        putenv('A1_BOOTSTRAP_PASSWORD=password12');
        $_ENV['A1_BOOTSTRAP_PASSWORD'] = 'password12';

        $options = [
            '--email' => $email,
            '--prenom' => 'Install',
            '--nom' => 'Livreaura',
            '--no-interaction' => true,
        ];

        $this->artisan('a1:bootstrap-install', $options)->assertSuccessful();
        $this->artisan('a1:bootstrap-install', $options)->assertSuccessful();

        $this->assertSame(1, Account::query()->where('email_normalise', $email)->count());
        $this->assertSame(1, ServiceConfiguration::query()->where('code', 'A1_acces')->count());
        $this->assertSame(1, LegalTextVersion::query()->where('type', 'cgu')->where('version', '1.0')->count());
    }

    public function test_bootstrap_does_not_promote_an_existing_public_account(): void
    {
        $email = $this->uniqueEmail();
        Account::factory()->create(['email_normalise' => $email]);
        putenv('A1_BOOTSTRAP_PASSWORD=password12');
        $_ENV['A1_BOOTSTRAP_PASSWORD'] = 'password12';

        $this->artisan('a1:bootstrap-install', [
            '--email' => $email,
            '--prenom' => 'Install',
            '--nom' => 'Livreaura',
            '--no-interaction' => true,
        ])->assertFailed();

        $admin = Role::query()->where('code', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertFalse(
            AccountRole::query()->where('role_id', $admin->id)->whereHas('account', fn ($query) => $query->where('email_normalise', $email))->exists()
        );
    }
}
