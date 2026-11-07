<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Verification\AttachVerificationEvidence;
use App\Modules\IdentityAccess\Application\Actions\Verification\OpenVerificationDossier;
use App\Modules\IdentityAccess\Application\Actions\Verification\SaveVerificationDraft;
use App\Modules\IdentityAccess\Application\Actions\Verification\SubmitVerificationDossier;
use App\Modules\IdentityAccess\Application\Queries\GetVerificationDossier;
use App\Modules\IdentityAccess\Application\Support\CanDepositVerification;
use App\Modules\IdentityAccess\Domain\Contracts\PrivateEvidenceStore;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDocument;
use App\Modules\IdentityAccess\Presentation\Http\Requests\AttachVerificationEvidenceRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\OpenVerificationDossierRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\SaveVerificationDraftRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\SubmitVerificationDossierRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationDossierController extends Controller
{
    public function store(OpenVerificationDossierRequest $request, OpenVerificationDossier $action): JsonResponse
    {
        $account = $this->account($request);
        $payload = $action(
            $account,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint(),
            $request->correlationId(),
        );
        $status = (int) ($payload['http_status'] ?? 201);
        unset($payload['http_status']);

        return IdentityApiResponse::success($payload, $status);
    }

    public function show(Request $request, string $id, GetVerificationDossier $query): JsonResponse
    {
        return IdentityApiResponse::success($query($this->account($request), $id));
    }

    public function update(
        SaveVerificationDraftRequest $request,
        string $id,
        SaveVerificationDraft $action,
    ): JsonResponse {
        return IdentityApiResponse::success($action(
            $this->account($request),
            $id,
            $request->payload(),
            $request->correlationId(),
        ));
    }

    public function storeEvidence(
        AttachVerificationEvidenceRequest $request,
        string $id,
        AttachVerificationEvidence $action,
    ): JsonResponse {
        return IdentityApiResponse::success($action(
            $this->account($request),
            $id,
            $request->payload(),
            $request->uploadedFile(),
            $request->correlationId(),
        ), 201);
    }

    public function submit(
        SubmitVerificationDossierRequest $request,
        string $id,
        SubmitVerificationDossier $action,
    ): JsonResponse {
        $account = $this->account($request);
        $payload = $action(
            $account,
            $id,
            $request->idempotencyKey($account),
            $request->intentionFingerprint($id),
            $request->correlationId(),
        );
        $status = (int) ($payload['http_status'] ?? 200);
        unset($payload['http_status']);

        return IdentityApiResponse::success($payload, $status);
    }

    public function downloadEvidence(
        Request $request,
        string $id,
        string $pieceId,
        CanDepositVerification $canDeposit,
        PrivateEvidenceStore $store,
    ): StreamedResponse {
        $account = $this->account($request);
        $document = VerificationDocument::query()
            ->with('dossier.subject')
            ->where('id', $pieceId)
            ->where('dossier_id', $id)
            ->first();

        if (
            $document === null
            || $document->dossier === null
            || $document->dossier->subject === null
            || ! $canDeposit($account, $document->dossier->subject)
        ) {
            throw new IdentityAccessException('piece_inconnue', 'Cette piece n est pas visible.', 404);
        }

        $contents = $store->read($document->reference_privee);

        if ($contents === null || $store->hashOf($document->reference_privee) !== $document->empreinte) {
            throw new IdentityAccessException(
                'fichier_inaccessible',
                'Cette piece n est plus accessible.',
                404,
            );
        }

        return response()->streamDownload(function () use ($contents): void {
            echo $contents;
        }, $document->type_doc.'.bin', [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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
