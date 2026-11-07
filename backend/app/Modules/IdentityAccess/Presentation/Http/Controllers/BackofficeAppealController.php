<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Support\AuthorizeAppealDocument;
use App\Modules\IdentityAccess\Application\Actions\Support\AuthorizeAppealSession;
use App\Modules\IdentityAccess\Application\Queries\ListBackofficeSupportDemands;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Presentation\Http\Requests\AuthorizeAppealDocumentRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\AuthorizeAppealSessionRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackofficeAppealController extends Controller
{
    public function index(Request $request, ListBackofficeSupportDemands $query): JsonResponse
    {
        $search = $request->query('q');
        $onglet = $request->query('onglet');
        $type = $request->query('type');
        $priorite = $request->query('priorite');

        return IdentityApiResponse::success($query($this->account($request), [
            'q' => is_string($search) ? mb_substr($search, 0, 120) : null,
            'onglet' => is_string($onglet) ? $onglet : 'a_traiter',
            'type' => is_string($type) ? $type : null,
            'priorite' => is_string($priorite) ? $priorite : null,
            'page' => max(1, $request->integer('page', 1)),
            'per_page' => $request->integer('per_page', 5),
        ]));
    }

    public function authorizeSession(
        AuthorizeAppealSessionRequest $request,
        string $id,
        AuthorizeAppealSession $action,
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

    public function authorizeDocument(
        AuthorizeAppealDocumentRequest $request,
        string $id,
        AuthorizeAppealDocument $action,
    ): JsonResponse {
        $account = $request->account();

        return IdentityApiResponse::success($action(
            $account,
            $id,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint($id),
            $request->correlationId(),
        ), 201);
    }

    private function account(Request $request): Account
    {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        return $account;
    }
}
