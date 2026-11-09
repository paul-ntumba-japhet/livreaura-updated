<?php

use App\Modules\Catalogue\Presentation\Http\Controllers\CatalogueConsultationController;
use App\Modules\Catalogue\Presentation\Http\Middleware\PrepareCatalogueRead;
use App\Modules\IdentityAccess\Presentation\Http\Middleware\AssignCorrelationId;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/catalogue')
    ->middleware([
        'api',
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AssignCorrelationId::class,
        PrepareCatalogueRead::class,
        'throttle:catalogue-public',
    ])
    ->group(function (): void {
        Route::get('/recherche', [CatalogueConsultationController::class, 'search'])->name('a2.catalogue.search');
        Route::get('/categories', [CatalogueConsultationController::class, 'categories'])->name('a2.catalogue.categories');
        Route::get('/langues', [CatalogueConsultationController::class, 'languages'])->name('a2.catalogue.languages');
        Route::get('/niveaux', [CatalogueConsultationController::class, 'levels'])->name('a2.catalogue.levels');
        Route::get('/editeurs', [CatalogueConsultationController::class, 'publishers'])->name('a2.catalogue.publishers');
        Route::get('/categories/{categorie}', [CatalogueConsultationController::class, 'category'])
            ->where('categorie', '.*')
            ->name('a2.catalogue.category');
        Route::get('/selections/{code}', [CatalogueConsultationController::class, 'selection'])->name('a2.catalogue.selection');
        Route::get('/oeuvres/{slug}', [CatalogueConsultationController::class, 'work'])->name('a2.catalogue.work');
        Route::get('/medias/{media}', [CatalogueConsultationController::class, 'media'])
            ->where('media', '[0-9A-HJKMNP-TV-Z]{26}')
            ->name('a2.catalogue.media');
        Route::get('/auteurs', [CatalogueConsultationController::class, 'authors'])->name('a2.catalogue.authors');
        Route::get('/auteurs/{slug}', [CatalogueConsultationController::class, 'author'])->name('a2.catalogue.author');
        Route::get('/editeurs/{slug}', [CatalogueConsultationController::class, 'publisher'])->name('a2.catalogue.publisher');
    });
