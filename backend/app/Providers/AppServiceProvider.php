<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        $this->configureTlsTrust();
    }

    private function configureTlsTrust(): void
    {
        if ((string) ini_get('openssl.cafile') !== '') {
            return;
        }

        $cafile = storage_path('certs/cacert.pem');

        if (! is_file($cafile)) {
            return;
        }

        ini_set('openssl.cafile', $cafile);
        ini_set('curl.cainfo', $cafile);
    }
}
