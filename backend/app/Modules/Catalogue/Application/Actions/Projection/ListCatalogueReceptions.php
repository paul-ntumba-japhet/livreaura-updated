<?php

namespace App\Modules\Catalogue\Application\Actions\Projection;

use App\Modules\Catalogue\Application\Projection\RecordCatalogueWork;
use App\Modules\Catalogue\Application\Support\CatalogueActor;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageReception;
use Illuminate\Http\Request;

final class ListCatalogueReceptions
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
            $this->actor->assert($account, $sessionId, 'reprendre_traitements_catalogue', ['support', 'admin']);
            $page = $request->query('page');
            $page = is_string($page) && preg_match('/^[1-9]\d*$/', $page) && (int) $page <= 10000
                ? (int) $page
                : ($page === null || $page === '' ? 1 : throw CatalogueConsultationException::invalid('page', 'Cette page n’est pas recevable.'));
            $query = MessageReception::query()
                ->where('consommateur', RecordCatalogueWork::CONSUMER)
                ->orderBy('recu_a')
                ->orderBy('id');
            $total = (clone $query)->count();
            $rows = $query->forPage($page, 20)->get();

            return [
                'travaux' => $rows->map(fn (MessageReception $row): array => [
                    'id' => $row->id,
                    'producteur' => $row->producteur,
                    'evenement_externe_id' => $row->evenement_externe_id,
                    'recu_a' => $row->recu_a?->toIso8601String(),
                    'traite_a' => $row->traite_a?->toIso8601String(),
                    'niveau_acquittement' => $row->niveau_acquittement,
                    'reference_resultat' => $row->reference_resultat,
                ])->all(),
                'page' => $page,
                'taille' => 20,
                'total' => $total,
            ];
        });
    }
}
