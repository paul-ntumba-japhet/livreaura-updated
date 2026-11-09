<?php

use App\Modules\Catalogue\Presentation\Http\Controllers\CatalogueHandoffController;
use App\Modules\Catalogue\Presentation\Http\Controllers\CatalogueShowcaseController;
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

$account = [...$public, 'auth:sanctum', 'a1.account.active', 'a1.session.authorized', 'a1.session.scope:normal'];

Route::prefix('api/v1/catalogue')
    ->middleware($public)
    ->group(function (): void {
        Route::post('/offres/{offre}/panier', [CatalogueHandoffController::class, 'cart'])->name('a2.handoff.cart');
    });

Route::prefix('api/v1/catalogue')
    ->middleware($account)
    ->group(function (): void {
        Route::post('/offres/{offre}/obtention-gratuite', [CatalogueHandoffController::class, 'free'])->name('a2.handoff.free');
        Route::post('/oeuvres/{slug}/lecture', [CatalogueHandoffController::class, 'reading'])->name('a2.handoff.reading');
    });

Route::prefix('api/v1/backoffice/catalogue')
    ->middleware($account)
    ->group(function (): void {
        Route::get('/selections', [CatalogueShowcaseController::class, 'selections'])->name('a2.showcase.index');
        Route::put('/selections/{selection}', [CatalogueShowcaseController::class, 'updateSelection'])->name('a2.showcase.selection');
        Route::put('/oeuvres/{oeuvre}/mise-en-avant', [CatalogueShowcaseController::class, 'updateHighlight'])->name('a2.showcase.highlight');
        Route::get('/avis', [CatalogueShowcaseController::class, 'reviews'])->name('a2.reviews.queue');
        Route::get('/travaux', [CatalogueShowcaseController::class, 'receptions'])->name('a2.projection.index');
        Route::post('/travaux/reprise', [CatalogueShowcaseController::class, 'resume'])->name('a2.projection.resume');
    });
