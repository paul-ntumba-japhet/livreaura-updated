<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Authentication\LogoutAccount;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;

class LogoutController extends Controller
{
    public function destroy(LogoutAccount $logout): JsonResponse
    {
        $logout();

        return IdentityApiResponse::empty();
    }
}
