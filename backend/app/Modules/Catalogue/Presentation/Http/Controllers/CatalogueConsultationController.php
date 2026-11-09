<?php

namespace App\Modules\Catalogue\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogue\Application\Actions\Discovery\BrowseCatalogue;
use App\Modules\Catalogue\Application\Actions\Discovery\SearchCatalogue;
use App\Modules\Catalogue\Application\Actions\Discovery\ServeCatalogueMedia;
use App\Modules\Catalogue\Application\Actions\Discovery\ShowContributor;
use App\Modules\Catalogue\Application\Actions\Discovery\ShowWork;
use App\Modules\Catalogue\Application\Queries\CatalogueCriteriaFactory;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Presentation\Http\Support\CatalogueApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CatalogueConsultationController extends Controller
{
    public function search(
        Request $request,
        CatalogueCriteriaFactory $criteria,
        SearchCatalogue $search,
    ): JsonResponse {
        return $this->respond(fn (): array => $search->page($criteria->fromSearch($request)));
    }

    public function categories(BrowseCatalogue $browse): JsonResponse
    {
        return $this->respond(fn (): array => $browse->categories());
    }

    public function languages(BrowseCatalogue $browse): JsonResponse
    {
        return $this->respond(fn (): array => $browse->languages());
    }

    public function levels(BrowseCatalogue $browse): JsonResponse
    {
        return $this->respond(fn (): array => $browse->levels());
    }

    public function publishers(BrowseCatalogue $browse): JsonResponse
    {
        return $this->respond(fn (): array => $browse->publishers());
    }

    public function category(Request $request, string $categorie, BrowseCatalogue $browse): JsonResponse
    {
        return $this->respond(fn (): array => $browse->category($categorie, $request));
    }

    public function selection(Request $request, string $code, BrowseCatalogue $browse): JsonResponse
    {
        return $this->respond(fn (): array => $browse->selection($code, $request));
    }

    public function work(Request $request, string $slug, ShowWork $work): JsonResponse
    {
        return $this->respond(fn (): array => $work->show($slug, $request));
    }

    public function authors(Request $request, ShowContributor $contributors): JsonResponse
    {
        return $this->respond(fn (): array => $contributors->authors($request));
    }

    public function author(Request $request, string $slug, ShowContributor $contributors): JsonResponse
    {
        return $this->respond(fn (): array => $contributors->author($slug, $request));
    }

    public function publisher(Request $request, string $slug, ShowContributor $contributors): JsonResponse
    {
        return $this->respond(fn (): array => $contributors->publisher($slug, $request));
    }

    public function media(string $media, ServeCatalogueMedia $serve): BinaryFileResponse|JsonResponse
    {
        try {
            $serve->authorize($media);
            $file = $serve->authorize($media);
            $response = new BinaryFileResponse($file['path'], 200, [], false, null, false, false);
            $response->headers->set('Content-Type', $file['mime']);
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->setContentDisposition('inline', $file['name']);

            return $response;
        } catch (CatalogueConsultationException $exception) {
            return CatalogueApiResponse::error(
                $exception->errorCode,
                $exception->getMessage(),
                $exception->status,
                $exception->fields,
            );
        }
    }

    /**
     * @param  callable(): array<string, mixed>  $read
     */
    private function respond(callable $read): JsonResponse
    {
        try {
            return CatalogueApiResponse::success($read());
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
