<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Registration\ActivateAccount;
use App\Modules\IdentityAccess\Application\Actions\Registration\IssueVerificationChallenge;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;
use App\Modules\IdentityAccess\Presentation\Http\Requests\ResendChallengeRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\VerifyEmailRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $challengeId = $request->query('defi_id');
        $payload = ['action' => 'activation'];

        if (is_string($challengeId) && $challengeId !== '') {
            $challenge = VerificationChallenge::query()->find($challengeId);

            if ($challenge !== null && $challenge->consomme_a === null && $challenge->revoque_a === null) {
                $payload['destination_masquee'] = $challenge->destination_masquee;
                $payload['expire_a'] = $challenge->expire_a?->toIso8601String();
            }
        }

        return IdentityApiResponse::success($payload);
    }

    public function store(VerifyEmailRequest $request, ActivateAccount $activate): JsonResponse
    {
        $correlation = $request->attributes->get('correlation_id');
        $account = $activate(
            $request->string('defi_id')->toString(),
            $request->string('preuve')->toString(),
            is_string($correlation) ? $correlation : '',
        );

        return IdentityApiResponse::success([
            'compte' => [
                'id' => $account->id,
                'statut' => $account->statut->value,
            ],
            'prochaine_action' => 'connexion',
        ]);
    }

    public function resend(ResendChallengeRequest $request, IssueVerificationChallenge $issue): JsonResponse
    {
        $issue->resendByEmail($request->string('email')->toString());

        return IdentityApiResponse::success([
            'statut' => 'verification_demandee',
        ], 202);
    }
}
