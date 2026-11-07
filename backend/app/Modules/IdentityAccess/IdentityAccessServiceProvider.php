<?php

namespace App\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Contracts\IdentityNotificationSender;
use App\Modules\IdentityAccess\Domain\Contracts\PrivateEvidenceStore;
use App\Modules\IdentityAccess\Infrastructure\Authentication\AccountUserProvider;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Infrastructure\Notifications\MailIdentityNotificationSender;
use App\Modules\IdentityAccess\Infrastructure\Notifications\SimulationIdentityNotificationSender;
use App\Modules\IdentityAccess\Infrastructure\Storage\LocalPrivateEvidenceStore;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountRole;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Presentation\Console\Commands\BootstrapIdentityInstallCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\DispatchTransmissionsCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\ExpireIdentityChallengesCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\ExpireIdentitySessionsCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\MonitorServiceTasksCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\PurgeTransientSecretsCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\ResumeInboxCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\ReviewVerificationExpiriesCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\SendVerificationRemindersCommand;
use App\Modules\IdentityAccess\Presentation\Http\Policies\AccountPolicy;
use App\Modules\IdentityAccess\Presentation\Http\Policies\OrganizationMembershipPolicy;
use App\Modules\IdentityAccess\Presentation\Http\Policies\RoleAssignmentPolicy;
use App\Modules\IdentityAccess\Presentation\Http\Policies\SessionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class IdentityAccessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SessionIdentityBridge::class);
        $this->app->singleton(SimulationIdentityNotificationSender::class);
        $this->app->singleton(MailIdentityNotificationSender::class);
        $this->app->singleton(IdentityNotificationSender::class, function ($app) {
            if ($app->make('config')->get('identity.notification_driver') === 'mail') {
                return $app->make(MailIdentityNotificationSender::class);
            }

            return $app->make(SimulationIdentityNotificationSender::class);
        });
        $this->app->singleton(PrivateEvidenceStore::class, LocalPrivateEvidenceStore::class);

        $this->app->resolving('auth', function ($auth): void {
            $auth->provider('account', function (): AccountUserProvider {
                return new AccountUserProvider;
            });
        });
    }

    public function boot(): void
    {
        RateLimiter::for('identity-public', function (Request $request) {
            return Limit::perMinute(30)->by((string) $request->ip());
        });

        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(AuthenticationSession::class, SessionPolicy::class);
        Gate::policy(OrganizationMembership::class, OrganizationMembershipPolicy::class);
        Gate::policy(AccountRole::class, RoleAssignmentPolicy::class);

        $this->loadRoutesFrom(__DIR__.'/Presentation/Http/Routes/session.php');
        $this->loadRoutesFrom(__DIR__.'/Presentation/Http/Routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                BootstrapIdentityInstallCommand::class,
                DispatchTransmissionsCommand::class,
                ExpireIdentityChallengesCommand::class,
                ExpireIdentitySessionsCommand::class,
                MonitorServiceTasksCommand::class,
                PurgeTransientSecretsCommand::class,
                ResumeInboxCommand::class,
                ReviewVerificationExpiriesCommand::class,
                SendVerificationRemindersCommand::class,
            ]);
        }
    }
}
