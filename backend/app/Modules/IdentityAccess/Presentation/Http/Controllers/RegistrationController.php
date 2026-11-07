<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Registration\RegisterAccount;
use App\Modules\IdentityAccess\Application\DTOs\RegistrationData;
use App\Modules\IdentityAccess\Domain\Support\DestinationMasker;
use App\Modules\IdentityAccess\Presentation\Http\Requests\RegisterAccountRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegistrationController extends Controller
{
    public function store(RegisterAccountRequest $request, RegisterAccount $register): JsonResponse
    {
        $data = new RegistrationData(
            path: $request->string('parcours')->toString(),
            email: $request->normalizedEmail(),
            passwordHash: Hash::make($request->string('password')->toString()),
            phone: $request->normalizedPhone(),
            locale: $request->string('locale', 'fr')->toString() ?: 'fr',
            firstName: $request->string('prenom')->toString(),
            lastName: $request->string('nom')->toString(),
            penName: $request->filled('nom_plume') ? $request->string('nom_plume')->toString() : null,
            country: strtoupper($request->string('pays')->toString()),
            city: $request->filled('ville') ? $request->string('ville')->toString() : null,
            writingLanguage: $request->filled('langue_ecriture') ? strtolower($request->string('langue_ecriture')->toString()) : null,
            consents: array_values($request->input('consentements', [])),
            idempotencyKey: $request->idempotencyKey(),
            intentionFingerprint: $request->intentionFingerprint(),
            correlationId: $request->correlationId(),
        );

        $account = $register($data);

        return IdentityApiResponse::success([
            'compte' => [
                'id' => $account->id,
                'statut' => $account->statut->value,
                'email_masque' => DestinationMasker::email($account->email_normalise),
            ],
            'prochaine_action' => 'verification_email',
        ], 201);
    }
}
