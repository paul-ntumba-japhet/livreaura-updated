<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Middleware;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssignCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->headers->get('X-Correlation-Id');
        $id = is_string($incoming) && IdentityUlid::isValid($incoming)
            ? $incoming
            : IdentityUlid::make();

        $request->attributes->set('correlation_id', $id);

        $response = $next($request);
        $response->headers->set('X-Correlation-Id', $id);

        return $response;
    }
}
