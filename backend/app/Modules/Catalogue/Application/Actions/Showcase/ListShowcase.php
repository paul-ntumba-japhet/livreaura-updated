<?php

namespace App\Modules\Catalogue\Application\Actions\Showcase;

use App\Modules\Catalogue\Application\Support\CatalogueActor;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Support\Facades\DB;

final class ListShowcase
{
    public function __construct(
        private CoordinationGuard $guard,
        private CatalogueActor $actor,
        private UpdateShowcase $showcase,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function list(Account $account, ?string $sessionId): array
    {
        return $this->guard->withinIdentity(function () use ($account, $sessionId): array {
            $this->actor->assert($account, $sessionId, 'gerer_vitrine', ['moderator', 'admin']);
            $selections = DB::table('selection_editoriale')
                ->orderBy('sort_order')
                ->orderBy('selection_id')
                ->get();
            $lines = DB::table('selection_oeuvre')->orderBy('oeuvre_id')->get()->groupBy('selection_id');

            return [
                'selections' => $selections->map(function (object $selection) use ($lines): array {
                    return $this->showcase->selectionState($selection, $lines->get($selection->selection_id, collect())->all());
                })->all(),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function highlight(Account $account, ?string $sessionId, string $workId): array
    {
        return $this->guard->withinIdentity(function () use ($account, $sessionId, $workId): array {
            $this->actor->assert($account, $sessionId, 'gerer_vitrine', ['moderator', 'admin']);
            $work = DB::table('oeuvre')->where('oeuvre_id', $workId)->first();

            if ($work === null) {
                throw \App\Modules\Catalogue\Domain\CatalogueConsultationException::absent();
            }

            return ['oeuvre' => $this->showcase->highlightState($work)];
        });
    }
}
