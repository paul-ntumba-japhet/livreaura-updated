<?php

namespace App\Modules\Catalogue\Application\Actions\Reviews;

use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ListPublishedReviews
{
    public function __construct(
        private ReviewWorkGate $works,
        private CatalogueVisibility $visibility,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function list(string $slug, Request $request): array
    {
        $work = $this->works->visible($slug);
        $page = $this->page($request->query('avis_page'));
        $size = $this->size($request->query('avis_taille'));

        $query = DB::table('avis')->where('avis.oeuvre_id', $work->oeuvre_id);
        $this->visibility->constrainPublicReview($query, $work->oeuvre_id);
        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc('avis.soumis_a')
            ->orderByDesc('avis.avis_id')
            ->forPage($page, $size)
            ->get(['avis.avis_id', 'avis.note', 'avis.corps', 'avis.soumis_a']);

        return [
            'oeuvre' => [
                'id' => $work->oeuvre_id,
                'slug' => $work->slug,
            ],
            'avis' => $rows->map(fn (object $row): array => [
                'id' => $row->avis_id,
                'note' => (int) $row->note,
                'corps' => $row->corps,
                'soumis_a' => $row->soumis_a,
            ])->all(),
            'page' => $page,
            'taille' => $size,
            'total' => $total,
        ];
    }

    private function page(mixed $value): int
    {
        return $this->bounded($value, 'avis_page', 1, 10000);
    }

    private function size(mixed $value): int
    {
        $size = $this->bounded($value, 'avis_taille', 10, 20);

        if ($value !== null && $value !== '' && $size > 20) {
            throw CatalogueConsultationException::invalid('avis_taille', 'Cette taille de page n’est pas recevable.');
        }

        return $size;
    }

    private function bounded(mixed $value, string $field, int $default, int $maximum): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (! is_string($value) || ! preg_match('/^[1-9]\d*$/', $value)) {
            throw CatalogueConsultationException::invalid($field, 'Cette valeur n’est pas recevable.');
        }

        $number = (int) $value;

        if ($number > $maximum) {
            throw CatalogueConsultationException::invalid($field, 'Cette valeur n’est pas recevable.');
        }

        return $number;
    }
}
