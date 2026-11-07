<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Support\OpenSuspensionAppeal;
use App\Modules\IdentityAccess\Presentation\Http\Requests\OpenSuspensionAppealRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;

class AppealController extends Controller
{
    public function store(OpenSuspensionAppealRequest $request, OpenSuspensionAppeal $action): JsonResponse
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
