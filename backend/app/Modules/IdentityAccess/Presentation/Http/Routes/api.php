<?php

use App\Modules\IdentityAccess\Presentation\Http\Controllers\AccountController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\OrganizationMembershipController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'a1.account.active', 'a1.session.authorized', 'a1.session.scope:normal'])
    ->prefix('api')
    ->group(function (): void {
        Route::get('/account', [AccountController::class, 'show'])->name('a1.account.show');
        Route::patch('/account', [AccountController::class, 'update'])->name('a1.account.update');
        Route::get('/sessions', [SessionController::class, 'index'])->name('a1.sessions.index');
        Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('a1.sessions.destroy');
        Route::post('/organization-memberships', [OrganizationMembershipController::class, 'store'])->name('a1.memberships.store');
        Route::delete('/organization-memberships/{membership}', [OrganizationMembershipController::class, 'destroy'])->name('a1.memberships.destroy');
    });
