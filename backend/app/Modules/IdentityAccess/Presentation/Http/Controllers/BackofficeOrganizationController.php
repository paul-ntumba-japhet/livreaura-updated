<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Organizations\TransferPrimaryRepresentative;
use App\Modules\IdentityAccess\Application\Queries\ListBackofficeOrganizations;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Presentation\Http\Requests\TransferPrimaryRepresentativeRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackofficeOrganizationController extends Controller
{
    public function index(Request $request, ListBackofficeOrganizations $query): JsonResponse
    {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        $search = $request->query('q');
        $onglet = $request->query('onglet');
        $typeId = $request->query('type_organisation_id');
        $verification = $request->query('verification');

        return IdentityApiResponse::success($query($account, [
            'q' => is_string($search) ? mb_substr($search, 0, 120) : null,
            'onglet' => is_string($onglet) ? $onglet : 'organisations',
            'type_organisation_id' => is_string($typeId) ? $typeId : null,
            'verification' => is_string($verification) ? $verification : null,
            'page' => max(1, $request->integer('page', 1)),
            'per_page' => $request->integer('per_page', 5),
        ]));
    }

    public function transferPrincipal(
        TransferPrimaryRepresentativeRequest $request,
        string $id,
        TransferPrimaryRepresentative $action,
    ): JsonResponse {
        $account = $request->account();
        $organizationId = strtoupper($id);

        return IdentityApiResponse::success($action(
            $account,
            $organizationId,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint($organizationId),
            $request->correlationId(),
        ));
    }
}
