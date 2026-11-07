<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Organizations\CreateOrRecognizeOrganization;
use App\Modules\IdentityAccess\Application\Queries\ListPossibleOrganizations;
use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Presentation\Http\Requests\CreateOrRecognizeOrganizationRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function matches(Request $request, ListPossibleOrganizations $query): JsonResponse
    {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        if ($account->statut !== AccountStatus::Active) {
            throw new IdentityAccessException(
                'compte_ineligible',
                'La recherche d organisation n est disponible qu pour un compte actif.',
                403,
            );
        }

        $typeId = (string) $request->query('type_organisation_id', '');
        $name = (string) $request->query('raison_sociale', '');
        $country = strtoupper((string) $request->query('pays', ''));

        return IdentityApiResponse::success([
            'correspondances' => $query($typeId, $name, $country),
        ]);
    }

    public function store(
        CreateOrRecognizeOrganizationRequest $request,
        CreateOrRecognizeOrganization $action,
    ): JsonResponse {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        $payload = $action(
            $account,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint(),
            $request->correlationId(),
        );
        $status = (int) ($payload['http_status'] ?? 200);
        unset($payload['http_status']);

        return IdentityApiResponse::success($payload, $status);
    }
}
