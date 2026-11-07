<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Support\AttachSupportEvidence;
use App\Modules\IdentityAccess\Application\Actions\Support\ReplyToSupport;
use App\Modules\IdentityAccess\Application\Queries\GetSupportDemand;
use App\Modules\IdentityAccess\Application\Support\ResolveSupportDemandAccess;
use App\Modules\IdentityAccess\Domain\Contracts\PrivateEvidenceStore;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportEvidence;
use App\Modules\IdentityAccess\Presentation\Http\Requests\AttachSupportEvidenceRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\ReplyToSupportRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportDemandController extends Controller
{
    public function show(Request $request, string $id, GetSupportDemand $query): JsonResponse
    {
        return IdentityApiResponse::success($query($this->account($request), $id));
    }

    public function storeExchange(
        ReplyToSupportRequest $request,
        string $id,
        ReplyToSupport $action,
    ): JsonResponse {
        $account = $request->account();
        $payload = $action(
            $account,
            $id,
            $request->payload(),
            $request->idempotencyKey($account, $id),
            $request->intentionFingerprint($id),
            $request->correlationId(),
        );
        $status = (int) ($payload['http_status'] ?? 201);
        unset($payload['http_status']);

        return IdentityApiResponse::success($payload, $status === 0 ? 201 : $status);
    }

    public function storeEvidence(
        AttachSupportEvidenceRequest $request,
        string $id,
        AttachSupportEvidence $action,
    ): JsonResponse {
        return IdentityApiResponse::success($action(
            $request->account(),
            $id,
            $request->payload(),
            $request->uploadedFile(),
            $request->correlationId(),
        ), 201);
    }

    public function downloadEvidence(
        Request $request,
        string $id,
        string $pieceId,
        ResolveSupportDemandAccess $resolveAccess,
        PrivateEvidenceStore $store,
    ): StreamedResponse {
        $account = $this->account($request);
        $access = $resolveAccess($account, $id);
        $document = SupportEvidence::query()
            ->where('id', strtoupper($pieceId))
            ->where('demande_id', $access->demande->id)
            ->first();

        if ($document === null) {
            throw new IdentityAccessException('piece_inconnue', 'Cette piece n est pas visible.', 404);
        }

        if ($document->acces === 'interne' && ! $access->seeInternal) {
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
        }, $document->type.'.bin', [
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
