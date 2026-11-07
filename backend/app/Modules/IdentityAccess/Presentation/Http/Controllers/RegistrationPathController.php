<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Queries\ListRegistrationPaths;
use App\Modules\IdentityAccess\Application\Support\EnsureOrganizationTypes;
use App\Modules\IdentityAccess\Application\Support\EnsureVerificationRequirements;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrationPathController extends Controller
{
    public function index(
        Request $request,
        ListRegistrationPaths $paths,
        EnsureOrganizationTypes $types,
        EnsureVerificationRequirements $requirements,
    ): JsonResponse {
        $langue = $request->query('langue', 'fr');
        $langue = is_string($langue) && $langue !== '' ? $langue : 'fr';

        $types();
        $requirements();

        return IdentityApiResponse::success(($paths)($langue));
    }
}
