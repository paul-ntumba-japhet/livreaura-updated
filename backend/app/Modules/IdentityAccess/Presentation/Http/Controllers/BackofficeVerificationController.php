<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IdentityAccess\Application\Actions\Tasks\AssignServiceTask;
use App\Modules\IdentityAccess\Application\Actions\Verification\DecideCommercialVerification;
use App\Modules\IdentityAccess\Application\Actions\Verification\DecideOrganizationVerification;
use App\Modules\IdentityAccess\Application\Actions\Verification\RecordVerificationControl;
use App\Modules\IdentityAccess\Application\Actions\Verification\RestrictVerificationCapability;
use App\Modules\IdentityAccess\Application\Actions\Verification\StartVerificationReview;
use App\Modules\IdentityAccess\Application\Queries\GetBackofficeVerificationDossier;
use App\Modules\IdentityAccess\Application\Queries\ListBackofficeCapabilities;
use App\Modules\IdentityAccess\Application\Queries\ListBackofficeKycDossiers;
use App\Modules\IdentityAccess\Application\Queries\ListBackofficeServiceTasks;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Application\Support\IsIndependentReviewer;
use App\Modules\IdentityAccess\Domain\Contracts\PrivateEvidenceStore;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDocument;
use App\Modules\IdentityAccess\Presentation\Http\Requests\BackofficeCorrelationRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\DecideCommercialVerificationRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\DecideOrganizationVerificationRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\RecordVerificationControlRequest;
use App\Modules\IdentityAccess\Presentation\Http\Requests\RestrictVerificationCapabilityRequest;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackofficeVerificationController extends Controller
{
    public function tasks(Request $request, ListBackofficeServiceTasks $query): JsonResponse
    {
        return IdentityApiResponse::success($query($this->account($request)));
    }

    public function index(Request $request, ListBackofficeKycDossiers $query): JsonResponse
    {
        $search = $request->query('q');
        $onglet = $request->query('onglet');
        $niveau = $request->query('niveau');
        $responsableId = $request->query('responsable_id');

        return IdentityApiResponse::success($query($this->account($request), [
            'q' => is_string($search) ? mb_substr($search, 0, 120) : null,
            'onglet' => is_string($onglet) ? $onglet : 'a_traiter',
            'niveau' => is_string($niveau) ? $niveau : null,
            'responsable_id' => is_string($responsableId) ? $responsableId : null,
            'page' => max(1, $request->integer('page', 1)),
            'per_page' => $request->integer('per_page', 5),
        ]));
    }

    public function capabilities(Request $request, ListBackofficeCapabilities $query): JsonResponse
    {
        $search = $request->query('q');
        $onglet = $request->query('onglet');
        $niveau = $request->query('niveau');
        $echeance = $request->query('echeance');

        return IdentityApiResponse::success($query($this->account($request), [
            'q' => is_string($search) ? mb_substr($search, 0, 120) : null,
            'onglet' => is_string($onglet) ? $onglet : 'toutes',
            'niveau' => is_string($niveau) ? $niveau : null,
            'echeance' => is_string($echeance) ? $echeance : null,
            'page' => max(1, $request->integer('page', 1)),
            'per_page' => $request->integer('per_page', 6),
        ]));
    }

    public function show(Request $request, string $id, GetBackofficeVerificationDossier $query): JsonResponse
    {
        return IdentityApiResponse::success($query($this->account($request), $id));
    }

    public function assign(
        BackofficeCorrelationRequest $request,
        string $id,
        AssignServiceTask $action,
    ): JsonResponse {
        return IdentityApiResponse::success($action(
            $request->account(),
            $id,
            $request->correlationId(),
        ));
    }

    public function takeCharge(
        BackofficeCorrelationRequest $request,
        string $id,
        StartVerificationReview $action,
    ): JsonResponse {
        return IdentityApiResponse::success($action(
            $request->account(),
            $id,
            $request->correlationId(),
        ));
    }

    public function recordControl(
        RecordVerificationControlRequest $request,
        string $id,
        RecordVerificationControl $action,
    ): JsonResponse {
        return IdentityApiResponse::success($action(
            $request->account(),
            $id,
            $request->payload(),
            $request->correlationId(),
        ));
    }

    public function decide(
        DecideOrganizationVerificationRequest $request,
        string $id,
        DecideOrganizationVerification $action,
    ): JsonResponse {
        $account = $request->account();

        return IdentityApiResponse::success($action(
            $account,
            $id,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint($id),
            $request->correlationId(),
        ));
    }

    public function decideCommercial(
        DecideCommercialVerificationRequest $request,
        string $id,
        DecideCommercialVerification $action,
    ): JsonResponse {
        $account = $request->account();

        return IdentityApiResponse::success($action(
            $account,
            $id,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint($id),
            $request->correlationId(),
        ));
    }

    public function restrict(
        RestrictVerificationCapabilityRequest $request,
        string $id,
        RestrictVerificationCapability $action,
    ): JsonResponse {
        $account = $request->account();

        return IdentityApiResponse::success($action(
            $account,
            $id,
            $request->payload(),
            $request->idempotencyKey($account),
            $request->intentionFingerprint($id),
            $request->correlationId(),
        ));
    }

    public function downloadEvidence(
        Request $request,
        string $id,
        string $pieceId,
        HasInternalPermission $hasPermission,
        IsIndependentReviewer $independent,
        PrivateEvidenceStore $store,
    ): StreamedResponse {
        $account = $this->account($request);

        if (! $hasPermission($account, 'controler_kyc')) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $document = VerificationDocument::query()
            ->with('dossier.subject')
            ->where('id', $pieceId)
            ->where('dossier_id', $id)
            ->first();

        if (
            $document === null
            || $document->dossier === null
            || $document->dossier->subject === null
            || $document->dossier->statut === 'draft'
        ) {
            throw new IdentityAccessException('piece_inconnue', 'Cette piece n est pas visible.', 404);
        }

        if (! $independent($account, $document->dossier)) {
            throw new IdentityAccessException(
                'conflit_interet',
                'Ce dossier est lie a ce compte. Une reaffectation independante est necessaire.',
                403,
            );
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
