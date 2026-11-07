<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Middleware;

use App\Modules\IdentityAccess\Domain\Enums\SessionAccessScope;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequiredAccessScope
{
    public function __construct(private SessionIdentityBridge $sessions) {}

    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $session = $this->sessions->current();

        if ($session === null) {
            abort(401);
        }

        $allowed = [];

        foreach ($scopes as $scope) {
            foreach (explode(',', $scope) as $candidate) {
                $value = trim($candidate);

                if ($value === '') {
                    continue;
                }

                $allowed[] = SessionAccessScope::from($value);
            }
        }

        if ($allowed === []) {
            abort(500, 'Portee de session requise absente.');
        }

        if (! in_array($session->portee, $allowed, true)) {
            throw new IdentityAccessException(
                'portee_insuffisante',
                'Cette session n a pas la portee requise.',
                403,
            );
        }

        return $next($request);
    }
}
