<?php

namespace App\Modules\Catalogue\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogue\Application\Actions\Projection\ListCatalogueReceptions;
use App\Modules\Catalogue\Application\Actions\Projection\ResumeCatalogueWork;
use App\Modules\Catalogue\Application\Actions\Reviews\ListReviewQueue;
use App\Modules\Catalogue\Application\Actions\Showcase\ListShowcase;
use App\Modules\Catalogue\Application\Actions\Showcase\UpdateShowcase;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Presentation\Http\Support\CatalogueApiResponse;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogueShowcaseController extends Controller
{
    public function selections(Request $request, ListShowcase $list, SessionIdentityBridge $sessions): JsonResponse
    {
        return $this->respond(fn (): array => $list->list($this->account($request), $sessions->currentId()));
    }

    public function updateSelection(
        Request $request,
        string $selection,
        UpdateShowcase $showcase,
        SessionIdentityBridge $sessions,
    ): JsonResponse {
        return $this->respond(fn (): array => $showcase->selection(
            $this->account($request),
            $sessions->currentId(),
            $selection,
            $request->all(),
            $request->headers->get('Idempotency-Key'),
            CatalogueApiResponse::correlationId(),
        ));
    }

    public function updateHighlight(
        Request $request,
        string $oeuvre,
        UpdateShowcase $showcase,
        SessionIdentityBridge $sessions,
    ): JsonResponse {
        return $this->respond(fn (): array => $showcase->highlight(
            $this->account($request),
            $sessions->currentId(),
            $oeuvre,
            $request->all(),
            $request->headers->get('Idempotency-Key'),
            CatalogueApiResponse::correlationId(),
        ));
    }

    public function reviews(Request $request, ListReviewQueue $queue, SessionIdentityBridge $sessions): JsonResponse
    {
        return $this->respond(fn (): array => $queue->list($this->account($request), $sessions->currentId(), $request));
    }

    public function receptions(Request $request, ListCatalogueReceptions $list, SessionIdentityBridge $sessions): JsonResponse
    {
        return $this->respond(fn (): array => $list->list($this->account($request), $sessions->currentId(), $request));
    }

    public function resume(Request $request, ResumeCatalogueWork $resume, SessionIdentityBridge $sessions): JsonResponse
    {
        return $this->respond(fn (): array => $resume->resume(
            $this->account($request),
            $sessions->currentId(),
            $request->all(),
            $request->headers->get('Idempotency-Key'),
            CatalogueApiResponse::correlationId(),
        ), 202);
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
