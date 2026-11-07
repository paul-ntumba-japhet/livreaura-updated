<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Support\OpenAccountClosure;
use App\Modules\IdentityAccess\Presentation\Http\Requests\OpenAccountClosureRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;

class ClosureDemandController extends Controller
{
    public function store(OpenAccountClosureRequest $request, OpenAccountClosure $action): JsonResponse
    {
        $payload = $action(
            $request->payload(),
            $request->idempotencyKey(),
            $request->intentionFingerprint(),
            $request->correlationId(),
        );
        $status = (int) ($payload['http_status'] ?? 202);
        unset($payload['http_status']);

        return IdentityApiResponse::success($payload, $status === 0 ? 202 : $status);
    }
}
