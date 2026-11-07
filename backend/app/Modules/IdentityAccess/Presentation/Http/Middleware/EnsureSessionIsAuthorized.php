<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Middleware;

use App\Modules\IdentityAccess\Application\Actions\Sessions\TouchAuthenticationSession;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionIsAuthorized
{
    public function __construct(
        private SessionIdentityBridge $sessions,
        private TouchAuthenticationSession $touchSession,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        $session = $this->sessions->current();

        if ($session === null || $session->compte_id !== $account->id) {
            abort(401);
        }

        if ($session->statut !== SessionStatus::Active) {
            abort(401);
        }

        if ($account->statut === AccountStatus::Closed) {
            abort(401);
        }

        if ($account->statut === AccountStatus::Suspended && $session->portee !== SessionAccessScope::SupportOnly) {
            abort(401);
        }

        $now = now();

        if ($session->expire_absolue_a <= $now || $session->expire_inactivite_a <= $now) {
            abort(401);
        }

        ($this->touchSession)($session);

        return $next($request);
    }
}
