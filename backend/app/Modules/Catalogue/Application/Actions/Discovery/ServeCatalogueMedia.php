<?php

namespace App\Modules\Catalogue\Application\Actions\Discovery;

use App\Modules\Catalogue\Application\Queries\CatalogueCriteria;
use App\Modules\Catalogue\Application\Queries\CatalogueOfferQuery;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use Illuminate\Support\Facades\DB;

final class ServeCatalogueMedia
{
    public function __construct(
        private CatalogueVisibility $visibility,
        private CatalogueOfferQuery $offers,
        private CurrentServiceConfiguration $configurations,
    ) {}

    /**
     * @return array{path: string, mime: string, name: string}
     */
    public function authorize(string $mediaId): array
    {
        $media = DB::table('media_catalogue')->where('media_catalogue_id', $mediaId)->first();
        if ($media === null || ! $this->isPublic($media) || ! $this->parentVisible($media)) {
            throw CatalogueConsultationException::absent();
        }

        $this->assertShape($media);

        return $this->derivative($media->reference_publique, $media->type);
    }

    private function isPublic(object $media): bool
    {
        $workId = $media->oeuvre_id ?? $this->editionWorkId($media->edition_id);
        if (! is_string($workId) || $workId === '') {
            return false;
        }

        $query = DB::table('media_catalogue')->where('media_catalogue.media_catalogue_id', $media->media_catalogue_id);
        $this->visibility->constrainPublicMedia($query, $workId);

        return $query->exists();
    }

    private function parentVisible(object $media): bool
    {
        if ($media->oeuvre_id !== null) {
            return $this->workVisible($media->oeuvre_id);
        }

        $edition = DB::table('edition')->where('edition_id', $media->edition_id)->first();
        if ($edition === null || ! $this->workVisible($edition->oeuvre_id)) {
            return false;
        }

        $query = DB::table('edition')
            ->join('oeuvre', 'oeuvre.oeuvre_id', '=', 'edition.oeuvre_id')
            ->where('edition.edition_id', $media->edition_id);
        $this->visibility->constrainVisibleEdition($query, 'edition', 'oeuvre');

        return $query->exists();
    }

    private function workVisible(string $workId): bool
    {
        $query = DB::table('oeuvre')->where('oeuvre.oeuvre_id', $workId);
        $this->visibility->constrainPublicWork($query);
        $criteria = new CatalogueCriteria(
            null, [], null, null, null, null, null, 'USD', null, null, null, null, null, null, null, null,
            'recent', 1, 20, CatalogueCriteria::FICHE,
        );
        $query->where(function ($visible) use ($criteria): void {
            $this->offers->whereMatchingOfferExists($visible, $criteria);
            $visible->orWhere('oeuvre.availability', 'soon');
        });

        return $query->exists();
    }

    private function editionWorkId(mixed $editionId): ?string
    {
        if (! is_string($editionId) || $editionId === '') {
            return null;
        }

        $workId = DB::table('edition')->where('edition_id', $editionId)->value('oeuvre_id');

        return is_string($workId) ? $workId : null;
    }

    private function assertShape(object $media): void
    {
        $volume = $media->volume_apercu;
        $unit = is_string($media->unite_volume) ? trim($media->unite_volume) : '';

        if ($media->type === 'couverture' || $media->type === 'galerie') {
            if ($volume !== null || $unit !== '') {
                throw CatalogueConsultationException::absent();
            }

            return;
        }

        if ($media->type !== 'apercu' || $volume === null || $unit === '' || $this->compare((string) $volume, '0') !== 1) {
            throw CatalogueConsultationException::absent();
        }

        $ceiling = $this->previewCeiling($unit);
        if ($ceiling === null) {
            throw CatalogueConsultationException::unavailable('La configuration de consultation est indisponible.');
        }
        if ($this->compare((string) $volume, $ceiling) === 1) {
            throw CatalogueConsultationException::absent();
        }
    }

    private function previewCeiling(string $unit): ?string
    {
        $configuration = $this->configurations->find('A2_consultation');
        $ceilings = $configuration?->valeurs['plafonds_apercu'] ?? null;
        if (! is_array($ceilings) || ! array_key_exists($unit, $ceilings) || ! is_numeric($ceilings[$unit])) {
            return null;
        }
        if ($this->compare((string) $ceilings[$unit], '0') !== 1) {
            return null;
        }

        return $this->decimal((string) $ceilings[$unit]);
    }

    /**
     * @return array{path: string, mime: string, name: string}
     */
    private function derivative(string $key, string $type): array
    {
        $key = str_replace('\\', '/', trim($key));
        if ($key === '' || str_contains($key, '..') || str_starts_with($key, '/') || str_contains($key, '://') || preg_match('/^[A-Za-z]:/', $key) === 1) {
            throw CatalogueConsultationException::absent();
        }

        $root = realpath(storage_path('app/private'));
        $candidate = $root === false ? false : $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $key);
        $path = is_string($candidate) && is_file($candidate) ? realpath($candidate) : false;
        if ($root === false || $path === false || ! $this->inside($path, $root)) {
            throw CatalogueConsultationException::absent();
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'pdf' => 'application/pdf',
            default => null,
        };
        if ($mime === null) {
            throw CatalogueConsultationException::absent();
        }

        return [
            'path' => $path,
            'mime' => $mime,
            'name' => $type.'.'.$extension,
        ];
    }

    private function inside(string $path, string $root): bool
    {
        $path = strtolower(str_replace('\\', '/', $path));
        $root = strtolower(rtrim(str_replace('\\', '/', $root), '/'));

        return str_starts_with($path, $root.'/');
    }

    private function compare(string $left, string $right): int
    {
        [$leftWhole, $leftFraction] = explode('.', $this->decimal($left));
        [$rightWhole, $rightFraction] = explode('.', $this->decimal($right));
        $width = max(strlen($leftWhole), strlen($rightWhole));
        $whole = strcmp(str_pad($leftWhole, $width, '0', STR_PAD_LEFT), str_pad($rightWhole, $width, '0', STR_PAD_LEFT));
        if ($whole !== 0) {
            return $whole < 0 ? -1 : 1;
        }

        return $leftFraction <=> $rightFraction;
    }

    private function decimal(string $value): string
    {
        if (! preg_match('/^\d+(\.\d+)?$/', $value)) {
            return '0';
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return $whole.'.'.str_pad(substr($fraction, 0, 8), 8, '0');
    }
}
