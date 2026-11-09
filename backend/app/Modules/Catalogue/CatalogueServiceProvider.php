<?php

namespace App\Modules\Catalogue;

use App\Modules\Catalogue\Application\Handoff\CatalogueAuthorityGateway;
use App\Modules\Catalogue\Application\Handoff\UnavailableCatalogueAuthority;
use App\Modules\Catalogue\Presentation\Console\Commands\DispatchCatalogueWorkCommand;
use App\Modules\Catalogue\Presentation\Console\Commands\ImportCatalogueRecipeCommand;
use App\Modules\Catalogue\Presentation\Console\Commands\RecalculateCatalogueAggregatesCommand;
use App\Modules\Catalogue\Presentation\Console\Commands\ResumeCatalogueCommand;
use App\Modules\Catalogue\Presentation\Http\Middleware\PrepareCatalogueRead;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class CatalogueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PrepareCatalogueRead::class);
        $this->app->singleton(CatalogueAuthorityGateway::class, UnavailableCatalogueAuthority::class);
    }

    public function boot(): void
    {
        RateLimiter::for('catalogue-public', function (Request $request) {
            return Limit::perMinute(60)->by((string) $request->ip());
        });

        $this->loadRoutesFrom(__DIR__.'/Presentation/Http/Routes/consultation.php');
        $this->loadRoutesFrom(__DIR__.'/Presentation/Http/Routes/reviews.php');
        $this->loadRoutesFrom(__DIR__.'/Presentation/Http/Routes/handoff.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportCatalogueRecipeCommand::class,
                DispatchCatalogueWorkCommand::class,
                ResumeCatalogueCommand::class,
                RecalculateCatalogueAggregatesCommand::class,
            ]);
        }
    }
}
