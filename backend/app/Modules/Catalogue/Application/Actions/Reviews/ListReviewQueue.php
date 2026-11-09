<?php

namespace App\Modules\Catalogue\Application\Actions\Reviews;

use App\Modules\Catalogue\Application\Support\CatalogueActor;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ListReviewQueue
{
    public function __construct(
        private CoordinationGuard $guard,
        private CatalogueActor $actor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function list(Account $account, ?string $sessionId, Request $request): array
    {
        return $this->guard->withinIdentity(function () use ($account, $sessionId, $request): array {
            $this->actor->assert($account, $sessionId, 'moderer_avis', ['moderator', 'admin']);
            $status = $request->query('statut', 'pending');

            if (! is_string($status) || ! in_array($status, ['pending', 'published', 'rejected'], true)) {
                throw CatalogueConsultationException::invalid('statut', 'Ce statut d’avis n’est pas recevable.');
            }

            $page = $this->page($request->query('page'));
            $size = $this->size($request->query('taille'));
            $query = DB::table('avis')
                ->join('oeuvre', 'oeuvre.oeuvre_id', '=', 'avis.oeuvre_id')
                ->where('avis.statut', $status);
            $total = (clone $query)->count();
            $rows = $query
                ->orderBy('avis.soumis_a')
                ->orderBy('avis.avis_id')
                ->forPage($page, $size)
                ->get([
                    'avis.avis_id',
                    'avis.oeuvre_id',
                    'oeuvre.slug',
                    'avis.note',
                    'avis.corps',
                    'avis.statut',
                    'avis.soumis_a',
                ]);

            return [
                'avis' => $rows->map(fn (object $row): array => [
                    'id' => $row->avis_id,
                    'oeuvre_id' => $row->oeuvre_id,
                    'slug' => $row->slug,
                    'note' => (int) $row->note,
                    'corps' => $row->corps,
                    'statut' => $row->statut,
                    'soumis_a' => $row->soumis_a,
                ])->all(),
                'page' => $page,
                'taille' => $size,
                'total' => $total,
            ];
        });
    }

    private function page(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 1;
        }

        if (! is_string($value) || ! preg_match('/^[1-9]\d*$/', $value) || (int) $value > 10000) {
            throw CatalogueConsultationException::invalid('page', 'Cette page n’est pas recevable.');
        }

        return (int) $value;
    }

    private function size(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 20;
        }

        if (! is_string($value) || ! preg_match('/^[1-9]\d*$/', $value) || (int) $value > 20) {
            throw CatalogueConsultationException::invalid('taille', 'Cette taille de page n’est pas recevable.');
        }

        return (int) $value;
    }
}
