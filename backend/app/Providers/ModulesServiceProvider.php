<?php

namespace App\Providers;

use App\Modules\IdentityAccess\IdentityAccessServiceProvider;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(IdentityAccessServiceProvider::class);
    }
}
