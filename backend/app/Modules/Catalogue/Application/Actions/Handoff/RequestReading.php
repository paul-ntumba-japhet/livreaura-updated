<?php

namespace App\Modules\Catalogue\Application\Actions\Handoff;

use App\Modules\Catalogue\Application\Actions\Reviews\ReviewWorkGate;
use App\Modules\Catalogue\Application\Handoff\CatalogueAuthorityGateway;
use App\Modules\Catalogue\Application\Support\CatalogueActor;
use App\Modules\Catalogue\Application\Support\CatalogueIntention;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Support\Facades\DB;

final class RequestReading
{
    public function __construct(
        private CoordinationGuard $guard,
        private CatalogueActor $actor,
        private ReviewWorkGate $works,
        private CatalogueIntention $intentions,
        private CatalogueAuthorityGateway $authority,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function request(Account $account, ?string $sessionId, string $slug, ?string $clientKey, string $correlation): array
    {
        $actor = $this->guard->withinIdentity(
            fn (): Account => $this->actor->assertSession($account, $sessionId),
        );
        $work = $this->works->visible($slug);
        $versions = DB::table('offres_contenu')
            ->join('offre', 'offre.offre_id', '=', 'offres_contenu.offre_id')
            ->join('edition', 'edition.edition_id', '=', 'offre.edition_id')
            ->where('edition.oeuvre_id', $work->oeuvre_id)
            ->where('offres_contenu.actif', 1)
            ->orderBy('offres_contenu.version_contenu_id')
            ->pluck('offres_contenu.version_contenu_id')
            ->map(fn (mixed $id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
        $key = $this->intentions->key('lecture', $actor->id, $work->oeuvre_id, $clientKey);

        return $this->authority->requestReading([
            'compte_id' => $actor->id,
            'oeuvre_id' => $work->oeuvre_id,
            'versions' => $versions,
            'correlation' => $correlation,
            'cle_idempotence' => $key,
        ]);
    }
}
