<?php

use App\Modules\IdentityAccess\Presentation\Http\Controllers\AccountController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\AppealController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\ClosureDemandController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\BackofficeAccountController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\BackofficeAppealController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\BackofficeOrganizationController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\BackofficeExploitationController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\BackofficePermissionController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\BackofficeVerificationController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\EmailVerificationController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\LoginController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\LogoutController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\OrganizationController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\PasswordResetController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\RegistrationController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\RegistrationPathController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\SessionController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\SupportDemandController;
use App\Modules\IdentityAccess\Presentation\Http\Controllers\VerificationDossierController;
use App\Modules\IdentityAccess\Presentation\Http\Middleware\AssignCorrelationId;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

$spa = [EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, AssignCorrelationId::class];

Route::prefix('api/v1/identity-access')
    ->middleware(['api', ...$spa, 'throttle:identity-public'])
    ->group(function (): void {
        Route::get('/parcours', [RegistrationPathController::class, 'index'])->name('a1.paths.index');
        Route::post('/inscriptions', [RegistrationController::class, 'store'])->name('a1.register');
        Route::post('/defis/renvoi', [EmailVerificationController::class, 'resend'])->name('a1.challenges.resend');
        Route::get('/activation', [EmailVerificationController::class, 'show'])->name('a1.activation.show');
        Route::post('/activation', [EmailVerificationController::class, 'store'])->name('a1.activation.store');
        Route::post('/connexion', [LoginController::class, 'store'])->name('a1.login');
        Route::post('/recuperations', [PasswordResetController::class, 'store'])->name('a1.recovery.store');
        Route::post('/recuperations/verification', [PasswordResetController::class, 'verify'])->name('a1.recovery.verify');
        Route::post('/recuperations/confirmation', [PasswordResetController::class, 'confirm'])->name('a1.recovery.confirm');
        Route::post('/recours', [AppealController::class, 'store'])->name('a1.appeals.store');
        Route::post('/cloture', [ClosureDemandController::class, 'store'])->name('a1.closures.store');
        Route::delete('/moi/sessions/courante', [LogoutController::class, 'destroy'])->name('a1.logout');
        Route::delete('/moi/sessions', [SessionController::class, 'destroyAll'])->name('a1.me.sessions.destroy');
    });

Route::prefix('api/v1/identity-access')
    ->middleware(['api', ...$spa, 'auth:sanctum', 'a1.session.authorized'])
    ->group(function (): void {
        Route::get('/moi', [AccountController::class, 'show'])->name('a1.me.show');
        Route::get('/moi/sessions', [SessionController::class, 'index'])->name('a1.me.sessions.index');
        Route::post('/moi/consentements', [AccountController::class, 'storeConsent'])->name('a1.me.consents');
    });

Route::prefix('api/v1/identity-access')
    ->middleware(['api', ...$spa, 'auth:sanctum', 'a1.session.authorized', 'a1.session.scope:normal'])
    ->group(function (): void {
        Route::post('/moi/parcours-auteur', [AccountController::class, 'storeAuthorPath'])->name('a1.me.author-path');
        Route::get('/organisations/correspondances', [OrganizationController::class, 'matches'])->name('a1.organizations.matches');
        Route::post('/organisations', [OrganizationController::class, 'store'])->name('a1.organizations.store');
        Route::post('/kyc/dossiers', [VerificationDossierController::class, 'store'])->name('a1.kyc.store');
        Route::get('/kyc/dossiers/{id}', [VerificationDossierController::class, 'show'])->name('a1.kyc.show');
        Route::put('/kyc/dossiers/{id}', [VerificationDossierController::class, 'update'])->name('a1.kyc.update');
        Route::post('/kyc/dossiers/{id}/pieces', [VerificationDossierController::class, 'storeEvidence'])->name('a1.kyc.evidence');
        Route::get('/kyc/dossiers/{id}/pieces/{pieceId}', [VerificationDossierController::class, 'downloadEvidence'])->name('a1.kyc.evidence.download');
        Route::post('/kyc/dossiers/{id}/soumission', [VerificationDossierController::class, 'submit'])->name('a1.kyc.submit');
        Route::put('/moi/mot-de-passe', [PasswordResetController::class, 'update'])->name('a1.me.password');
    });

Route::prefix('api/v1/identity-access')
    ->middleware(['api', ...$spa, 'auth:sanctum', 'a1.session.authorized', 'a1.session.scope:normal,support_only'])
    ->group(function (): void {
        Route::get('/support/{id}', [SupportDemandController::class, 'show'])->name('a1.support.show');
        Route::post('/support/{id}/echanges', [SupportDemandController::class, 'storeExchange'])->name('a1.support.exchanges');
        Route::post('/support/{id}/pieces', [SupportDemandController::class, 'storeEvidence'])->name('a1.support.evidence');
        Route::get('/support/{id}/pieces/{pieceId}', [SupportDemandController::class, 'downloadEvidence'])->name('a1.support.evidence.download');
    });

Route::prefix('api/v1/backoffice/identity-access')
    ->middleware(['api', ...$spa, 'auth:sanctum', 'a1.session.authorized', 'a1.session.scope:normal'])
    ->group(function (): void {
        Route::get('/taches', [BackofficeVerificationController::class, 'tasks'])->name('a1.backoffice.tasks.index');
        Route::post('/taches/{id}/affectation', [BackofficeVerificationController::class, 'assign'])->name('a1.backoffice.tasks.assign');
        Route::get('/kyc', [BackofficeVerificationController::class, 'index'])->name('a1.backoffice.kyc.index');
        Route::get('/kyc/{id}', [BackofficeVerificationController::class, 'show'])->name('a1.backoffice.kyc.show');
        Route::post('/kyc/{id}/prise-en-charge', [BackofficeVerificationController::class, 'takeCharge'])->name('a1.backoffice.kyc.take');
        Route::post('/kyc/{id}/controles', [BackofficeVerificationController::class, 'recordControl'])->name('a1.backoffice.kyc.controls');
        Route::post('/kyc/{id}/decision', [BackofficeVerificationController::class, 'decide'])->name('a1.backoffice.kyc.decide');
        Route::post('/kyc/{id}/decision-commerciale', [BackofficeVerificationController::class, 'decideCommercial'])->name('a1.backoffice.kyc.decide-commercial');
        Route::get('/habilitations', [BackofficeVerificationController::class, 'capabilities'])->name('a1.backoffice.capabilities.index');
        Route::post('/habilitations/{id}/restriction', [BackofficeVerificationController::class, 'restrict'])->name('a1.backoffice.capabilities.restrict');
        Route::get('/organisations', [BackofficeOrganizationController::class, 'index'])->name('a1.backoffice.organizations.index');
        Route::post('/organisations/{id}/transfert-principal', [BackofficeOrganizationController::class, 'transferPrincipal'])->name('a1.backoffice.organizations.transfer-principal');
        Route::get('/comptes', [BackofficeAccountController::class, 'index'])->name('a1.backoffice.accounts.index');
        Route::post('/comptes/{id}/suspension', [BackofficeAccountController::class, 'suspend'])->name('a1.backoffice.accounts.suspend');
        Route::post('/comptes/{id}/reactivation', [BackofficeAccountController::class, 'reactivate'])->name('a1.backoffice.accounts.reactivate');
        Route::post('/comptes/{id}/cloture', [BackofficeAccountController::class, 'close'])->name('a1.backoffice.accounts.close');
        Route::post('/cloture/{id}/identite', [BackofficeAccountController::class, 'verifyClosureIdentity'])->name('a1.backoffice.closures.identity');
        Route::get('/recours', [BackofficeAppealController::class, 'index'])->name('a1.backoffice.appeals.index');
        Route::post('/recours/{id}/autorisation-session', [BackofficeAppealController::class, 'authorizeSession'])->name('a1.backoffice.appeals.authorize-session');
        Route::post('/recours/{id}/acces-documents', [BackofficeAppealController::class, 'authorizeDocument'])->name('a1.backoffice.appeals.authorize-document');
        Route::get('/equipe', [BackofficeExploitationController::class, 'team'])->name('a1.backoffice.team.index');
        Route::get('/permissions', [BackofficePermissionController::class, 'index'])->name('a1.backoffice.permissions.index');
        Route::post('/permissions', [BackofficePermissionController::class, 'store'])->name('a1.backoffice.permissions.store');
        Route::post('/permissions/{id}/revocation', [BackofficePermissionController::class, 'revoke'])->name('a1.backoffice.permissions.revoke');
        Route::get('/configurations', [BackofficeExploitationController::class, 'configurations'])->name('a1.backoffice.configurations.index');
        Route::post('/configurations', [BackofficeExploitationController::class, 'approve'])->name('a1.backoffice.configurations.store');
        Route::get('/suppleances', [BackofficeExploitationController::class, 'substitutes'])->name('a1.backoffice.substitutes.index');
        Route::post('/suppleances', [BackofficeExploitationController::class, 'assignSubstitute'])->name('a1.backoffice.substitutes.store');
        Route::get('/transmissions', [BackofficeExploitationController::class, 'transmissions'])->name('a1.backoffice.transmissions.index');
        Route::get('/supervision', [BackofficeExploitationController::class, 'supervision'])->name('a1.backoffice.supervision.index');
        Route::post('/transmissions/{id}/reprise', [BackofficeExploitationController::class, 'retryTransmission'])->name('a1.backoffice.transmissions.retry');
        Route::get('/kyc/{id}/pieces/{pieceId}', [BackofficeVerificationController::class, 'downloadEvidence'])->name('a1.backoffice.kyc.evidence.download');
    });
