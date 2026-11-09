<?php

namespace App\Modules\Catalogue\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogue\Application\Actions\Handoff\RequestCartAddition;
use App\Modules\Catalogue\Application\Actions\Handoff\RequestFreeAcquisition;
use App\Modules\Catalogue\Application\Actions\Handoff\RequestReading;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Presentation\Http\Support\CatalogueApiResponse;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogueHandoffController extends Controller
{
    public function cart(Request $request, string $offre, RequestCartAddition $action): JsonResponse
    {
        return $this->respond(fn (): array => $action->request(
            $offre,
            ['quantite' => $request->input('quantite')],
            $request->headers->get('Idempotency-Key'),
            CatalogueApiResponse::correlationId(),
        ));
    }

    public function free(Request $request, string $offre, RequestFreeAcquisition $action, SessionIdentityBridge $sessions): JsonResponse
    {
        return $this->respond(fn (): array => $action->request(
            $this->account($request),
            $sessions->currentId(),
            $offre,
            $request->headers->get('Idempotency-Key'),
            CatalogueApiResponse::correlationId(),
        ));
    }

    public function reading(Request $request, string $slug, RequestReading $action, SessionIdentityBridge $sessions): JsonResponse
    {
        return $this->respond(fn (): array => $action->request(
            $this->account($request),
            $sessions->currentId(),
            $slug,
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
    private function respond(callable $action): JsonResponse
    {
        try {
            return CatalogueApiResponse::success($action());
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
