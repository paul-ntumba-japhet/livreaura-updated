<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Authentication\AuthenticateAccount;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Presentation\Http\Requests\LoginRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(
        LoginRequest $request,
        AuthenticateAccount $authenticate,
        SessionIdentityBridge $sessions,
    ): JsonResponse {
        $correlation = $request->attributes->get('correlation_id');
        $result = $authenticate(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            is_string($correlation) ? $correlation : '',
        );

        $request->session()->regenerate();
        Auth::guard('web')->login($result->account);
        $sessions->bind($result->session->id, $result->reference);

        return IdentityApiResponse::success([
            'compte' => [
                'id' => $result->account->id,
                'statut' => $result->account->statut->value,
            ],
            'session' => [
                'portee' => $result->scope->value,
                'expire_absolue_a' => $result->session->expire_absolue_a?->toIso8601String(),
            ],
        ]);
    }
}
