<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Authorization\GrantInternalPermission;
use App\Modules\IdentityAccess\Application\Actions\Authorization\RevokeInternalPermission;
use App\Modules\IdentityAccess\Application\Queries\ListInternalPermissionCatalog;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Presentation\Http\Requests\GrantInternalPermissionRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\RevokeInternalPermissionRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackofficePermissionController extends Controller
{
    public function index(Request $request, ListInternalPermissionCatalog $query): JsonResponse
    {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        $compteId = $request->query('compte_id');

        return IdentityApiResponse::success($query(
            $account,
            is_string($compteId) ? $compteId : null,
        ));
    }

    public function store(GrantInternalPermissionRequest $request, GrantInternalPermission $action): JsonResponse
    {
        $account = $request->account();
        $payload = $action(
            $account,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint(),
            $request->correlationId(),
        );

        return IdentityApiResponse::success($payload, 201);
    }

    public function revoke(
        RevokeInternalPermissionRequest $request,
        string $id,
        RevokeInternalPermission $action,
    ): JsonResponse {
        $account = $request->account();
        $attributionId = $request->attributionId($id);

        return IdentityApiResponse::success($action(
            $account,
            $attributionId,
            $request->payload(),
            $request->idempotencyKey($account, $attributionId),
            $request->intentionFingerprint($attributionId),
            $request->correlationId(),
        ));
    }
}
