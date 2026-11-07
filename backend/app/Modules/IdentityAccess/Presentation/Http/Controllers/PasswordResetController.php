<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Passwords\ChangePassword;
use App\Modules\IdentityAccess\Application\Actions\Passwords\CheckResetProof;
use App\Modules\IdentityAccess\Application\Actions\Passwords\RequestPasswordReset;
use App\Modules\IdentityAccess\Application\Actions\Passwords\ResetPassword;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Presentation\Http\Requests\ChangePasswordRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\CheckResetProofRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\ConfirmPasswordResetRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\RequestPasswordResetRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function store(RequestPasswordResetRequest $request, RequestPasswordReset $action): JsonResponse
    {
        $action($request->string('email')->toString());

        return IdentityApiResponse::success([
            'statut' => 'recuperation_demandee',
        ], 202);
    }

    public function verify(CheckResetProofRequest $request, CheckResetProof $action): JsonResponse
    {
        $payload = $action(
            $request->string('defi_id')->toString(),
            $request->string('preuve')->toString(),
        );

        return IdentityApiResponse::success([
            'formulaire' => 'autorise',
            ...$payload,
        ]);
    }

    public function confirm(ConfirmPasswordResetRequest $request, ResetPassword $action): JsonResponse
    {
        $correlation = $request->attributes->get('correlation_id');
        $action(
            $request->string('defi_id')->toString(),
            $request->string('preuve')->toString(),
            Hash::make($request->string('password')->toString()),
            is_string($correlation) ? $correlation : '',
        );

        return IdentityApiResponse::success([
            'prochaine_action' => 'connexion',
        ]);
    }

    public function update(
        ChangePasswordRequest $request,
        ChangePassword $action,
        SessionIdentityBridge $sessions,
    ): JsonResponse {
        $account = $request->user();

        if (! $account instanceof Account) {
            abort(401);
        }

        $correlation = $request->attributes->get('correlation_id');
        $action(
            $account,
            $request->string('password_actuel')->toString(),
            Hash::make($request->string('password')->toString()),
            is_string($correlation) ? $correlation : '',
        );

        $sessions->bind(null);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return IdentityApiResponse::success([
            'prochaine_action' => 'connexion',
        ]);
    }
}
