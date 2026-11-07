<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Sessions\RevokeOwnSessions;
use App\Modules\IdentityAccess\Application\Queries\ListAccountSessions;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request, ListAccountSessions $query): JsonResponse
    {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        return IdentityApiResponse::success($query($account));
    }

    public function destroyAll(RevokeOwnSessions $revoke): JsonResponse
    {
        $revoke();

        return IdentityApiResponse::empty();
    }
}
