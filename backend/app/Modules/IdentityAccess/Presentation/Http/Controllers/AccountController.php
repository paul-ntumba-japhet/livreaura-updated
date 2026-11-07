<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Accounts\EnableAuthorPath;
use App\Modules\IdentityAccess\Application\Actions\Consent\RecordConsentResponse;
use App\Modules\IdentityAccess\Application\Queries\GetAuthenticatedAccount;
use App\Modules\IdentityAccess\Application\Queries\ListAccountConsentState;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Presentation\Http\Requests\EnableAuthorPathRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\RecordConsentRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request, GetAuthenticatedAccount $query): JsonResponse
    {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        return IdentityApiResponse::success($query($account));
    }

    public function storeConsent(
        RecordConsentRequest $request,
        RecordConsentResponse $action,
        ListAccountConsentState $state,
    ): JsonResponse {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        $consent = $action(
            $account,
            $request->string('texte_version_id')->toString(),
            $request->action(),
            $request->filled('nature') ? $request->string('nature')->toString() : null,
        );

        return IdentityApiResponse::success([
            'consentement' => $state->serialize($consent),
            ...$state($account),
        ]);
    }

    public function storeAuthorPath(EnableAuthorPathRequest $request, EnableAuthorPath $action): JsonResponse
    {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        $payload = $action(
            $account,
            $request->claimedAuthorId(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint(),
            $request->correlationId(),
        );
        $status = (int) ($payload['http_status'] ?? 200);
        unset($payload['http_status']);

        return IdentityApiResponse::success($payload, $status);
    }

    public function update(): never
    {
        abort(501, 'Parcours A1 non implemente.');
    }
}
