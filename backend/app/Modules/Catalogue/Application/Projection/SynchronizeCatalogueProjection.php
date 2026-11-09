<?php

namespace App\Modules\Catalogue\Application\Projection;

use App\Modules\Catalogue\Domain\Rules\CatalogueVisibility;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageReception;
use Illuminate\Support\Facades\DB;

final class SynchronizeCatalogueProjection
{
    public function __construct(
        private CoordinationGuard $guard,
        private CatalogueVisibility $visibility,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forReception(string $receptionId): array
    {
        return $this->guard->withinIdentityThenCatalog(function () use ($receptionId): array {
            $reception = MessageReception::query()->where('id', $receptionId)->lockForUpdate()->first();

            if ($reception === null || $reception->producteur !== 'A2' || $reception->consommateur !== RecordCatalogueWork::CONSUMER) {
                return ['niveau_acquittement' => null];
            }

            if ($reception->traite_a !== null) {
                return [
                    'niveau_acquittement' => $reception->niveau_acquittement,
                    'reference_resultat' => $reception->reference_resultat,
                ];
            }

            $data = is_array($reception->donnees_minimales) ? $reception->donnees_minimales : [];
            $reference = $this->reread($data);
            $reception->traite_a = now();
            $reception->niveau_acquittement = 'engage_local';
            $reception->reference_resultat = $reference;
            $reception->save();

            return [
                'niveau_acquittement' => 'engage_local',
                'reference_resultat' => $reference,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $target
     */
    private function reread(array $target): ?string
    {
        $kind = $target['cible'] ?? null;
        $id = $target['id'] ?? null;

        if (! is_string($id) || $id === '') {
            return null;
        }

        if ($kind === 'selection') {
            $selection = DB::table('selection_editoriale')->where('selection_id', $id)->first();

            return $selection === null ? null : $selection->selection_id;
        }

        if ($kind === 'oeuvre') {
            $query = DB::table('oeuvre')->where('oeuvre.oeuvre_id', $id);
            $this->visibility->constrainPublicWork($query);
            $query->first();

            return $id;
        }

        return null;
    }
}
