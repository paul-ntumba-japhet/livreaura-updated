<?php

use App\Modules\Catalogue\Presentation\Http\Controllers\CatalogueReviewController;
use App\Modules\Catalogue\Presentation\Http\Middleware\PrepareCatalogueRead;
use App\Modules\IdentityAccess\Presentation\Http\Middleware\AssignCorrelationId;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

$public = [
    'api',
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    AssignCorrelationId::class,
    PrepareCatalogueRead::class,
    'throttle:catalogue-public',
];

Route::prefix('api/v1/catalogue')
    ->middleware($public)
    ->group(function (): void {
        Route::get('/oeuvres/{slug}/avis', [CatalogueReviewController::class, 'index'])->name('a2.reviews.index');
    });

Route::prefix('api/v1/catalogue')
    ->middleware([...$public, 'auth:sanctum', 'a1.account.active', 'a1.session.authorized', 'a1.session.scope:normal'])
    ->group(function (): void {
        Route::post('/oeuvres/{slug}/avis', [CatalogueReviewController::class, 'store'])->name('a2.reviews.store');
    });

Route::prefix('api/v1/backoffice/catalogue')
    ->middleware([...$public, 'auth:sanctum', 'a1.account.active', 'a1.session.authorized', 'a1.session.scope:normal'])
    ->group(function (): void {
        Route::post('/avis/{avis}/{decision}', [CatalogueReviewController::class, 'decide'])
            ->whereIn('decision', ['publication', 'rejet', 'retrait'])
            ->name('a2.reviews.decide');
    });
