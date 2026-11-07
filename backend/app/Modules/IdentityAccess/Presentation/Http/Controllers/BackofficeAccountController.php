<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Accounts\CloseAccount;
use App\Modules\IdentityAccess\Application\Actions\Accounts\ReactivateAccount;
use App\Modules\IdentityAccess\Application\Actions\Accounts\SuspendAccount;
use App\Modules\IdentityAccess\Application\Actions\Support\VerifyClosureIdentity;
use App\Modules\IdentityAccess\Application\Queries\ListBackofficeAccounts;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Presentation\Http\Requests\CloseAccountRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\ReactivateAccountRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\SuspendAccountRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\VerifyClosureIdentityRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackofficeAccountController extends Controller
{
    public function index(Request $request, ListBackofficeAccounts $query): JsonResponse
    {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        $search = $request->query('q');
        $statut = $request->query('statut');
        $role = $request->query('role');
        $verified = $request->query('email_verifie');

        return IdentityApiResponse::success($query($account, [
            'q' => is_string($search) ? mb_substr($search, 0, 120) : null,
            'statut' => is_string($statut) ? $statut : null,
            'role' => is_string($role) ? $role : null,
            'email_verifie' => is_string($verified) ? $verified : null,
            'page' => max(1, $request->integer('page', 1)),
            'per_page' => $request->integer('per_page', 6),
        ]));
    }

    public function suspend(
        SuspendAccountRequest $request,
        string $id,
        SuspendAccount $action,
    ): JsonResponse {
        $account = $request->account();
        $targetId = $request->targetAccountId($id);

        return IdentityApiResponse::success($action(
            $account,
            $targetId,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint($targetId),
            $request->correlationId(),
        ));
    }

    public function reactivate(
        ReactivateAccountRequest $request,
        string $id,
        ReactivateAccount $action,
    ): JsonResponse {
        $account = $request->account();
        $targetId = $request->targetAccountId($id);

        return IdentityApiResponse::success($action(
            $account,
            $targetId,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint($targetId),
            $request->correlationId(),
        ));
    }

    public function close(
        CloseAccountRequest $request,
        string $id,
        CloseAccount $action,
    ): JsonResponse {
        $account = $request->account();
        $targetId = $request->targetAccountId($id);

        return IdentityApiResponse::success($action(
            $account,
            $targetId,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint($targetId),
            $request->correlationId(),
        ));
    }

    public function verifyClosureIdentity(
        VerifyClosureIdentityRequest $request,
        string $id,
        VerifyClosureIdentity $action,
    ): JsonResponse {
        $account = $request->account();

        return IdentityApiResponse::success($action(
            $account,
            $id,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint($id),
            $request->correlationId(),
        ));
    }
}
