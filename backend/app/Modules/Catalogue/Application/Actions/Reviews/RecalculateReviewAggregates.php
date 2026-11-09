<?php

namespace App\Modules\Catalogue\Application\Actions\Reviews;

use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use Illuminate\Support\Facades\DB;

final class RecalculateReviewAggregates
{
    public function __construct(
        private CoordinationGuard $guard,
        private CatalogueVisibility $visibility,
    ) {}

    /**
     * @return array{note_moyenne: string|null, avis_count: int}
     */
    public function forWork(string $workId): array
    {
        return $this->guard->withinIdentityThenCatalog(fn (): array => $this->apply($workId));
    }

    /**
     * @return array{note_moyenne: string|null, avis_count: int}
     */
    public function apply(string $workId): array
    {
        $locked = DB::table('oeuvre')->where('oeuvre_id', $workId)->lockForUpdate()->first();

        if ($locked === null) {
            return ['note_moyenne' => null, 'avis_count' => 0];
        }

        $query = DB::table('avis')->where('avis.oeuvre_id', $workId);
        $this->visibility->constrainPublicReview($query, $workId);
        $count = (int) (clone $query)->count();
        $average = $count === 0 ? null : $query->avg('avis.note');
        $stored = $average === null ? null : number_format((float) $average, 8, '.', '');

        DB::table('oeuvre')->where('oeuvre_id', $workId)->update([
            'note_moyenne' => $stored,
            'avis_count' => $count,
        ]);

        return [
            'note_moyenne' => $stored,
            'avis_count' => $count,
        ];
    }
}
