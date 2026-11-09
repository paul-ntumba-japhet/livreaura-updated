<?php

namespace App\Modules\Catalogue\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogue\Application\Actions\Reviews\DecideReview;
use App\Modules\Catalogue\Application\Actions\Reviews\ListPublishedReviews;
use App\Modules\Catalogue\Application\Actions\Reviews\SubmitReview;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Presentation\Http\Support\CatalogueApiResponse;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogueReviewController extends Controller
{
    public function index(Request $request, string $slug, ListPublishedReviews $reviews): JsonResponse
    {
        return $this->respond(fn (): array => $reviews->list($slug, $request));
    }

    public function store(Request $request, string $slug, SubmitReview $submit, SessionIdentityBridge $sessions): JsonResponse
    {
        return $this->respond(fn (): array => $submit->submit(
            $this->account($request),
            $sessions->currentId(),
            $slug,
            [
                'note' => $request->input('note'),
                'corps' => $request->input('corps'),
            ],
            $request->headers->get('Idempotency-Key'),
            CatalogueApiResponse::correlationId(),
        ), 201);
    }

    public function decide(
        Request $request,
        string $avis,
        string $decision,
        DecideReview $decide,
        SessionIdentityBridge $sessions,
    ): JsonResponse {
        return $this->respond(fn (): array => $decide->decide(
            $this->account($request),
            $sessions->currentId(),
            $avis,
            $decision,
            [
                'statut_attendu' => $request->input('statut_attendu'),
                'motif' => $request->input('motif'),
            ],
            $request->headers->get('Idempotency-Key'),
            CatalogueApiResponse::correlationId(),
        ));
    }

    private function account(Request $request): Account
    {
        $account = $request->user();

        if (! $account instanceof Account) {
            throw CatalogueConsultationException::unauthenticated();
        }

        return $account;
    }

    /**
     * @param  callable(): array<string, mixed>  $action
     */
    private function respond(callable $action, int $status = 200): JsonResponse
    {
        try {
            return CatalogueApiResponse::success($action(), $status);
        } catch (CatalogueConsultationException $exception) {
            return CatalogueApiResponse::error(
                $exception->errorCode,
                $exception->getMessage(),
                $exception->status,
                $exception->fields,
            );
        }
    }
}
