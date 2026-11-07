<?php

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Presentation\Http\Middleware\EnsureAccountIsActive;
use App\Modules\IdentityAccess\Presentation\Http\Middleware\EnsureRequiredAccessScope;
use App\Modules\IdentityAccess\Presentation\Http\Middleware\EnsureSessionIsAuthorized;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'a1.account.active' => EnsureAccountIsActive::class,
            'a1.session.authorized' => EnsureSessionIsAuthorized::class,
            'a1.session.scope' => EnsureRequiredAccessScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (IdentityAccessException $exception, Request $request) {
            return IdentityApiResponse::error(
                $exception->errorCode,
                $exception->getMessage(),
                $exception->status,
                $exception->fields,
            );
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/v1/identity-access/*') && ! $request->is('api/v1/backoffice/identity-access/*') && ! $request->expectsJson()) {
                return null;
            }

            return IdentityApiResponse::error(
                'saisie_invalide',
                'Les donnees saisies sont invalides.',
                422,
                $exception->errors(),
            );
        });
    })->create();
