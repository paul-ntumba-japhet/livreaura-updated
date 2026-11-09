<?php

namespace App\Modules\Catalogue\Application\Projection;

use App\Modules\Catalogue\Application\Actions\Reviews\RecalculateReviewAggregates;
use App\Modules\Catalogue\Domain\CatalogueConsultationException;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\EffectConfirmation;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageReception;
use Illuminate\Support\Facades\DB;

final class ApplyCatalogueRestriction
{
    public function __construct(
        private CoordinationGuard $guard,
        private RecalculateReviewAggregates $aggregates,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forReception(string $receptionId): array
    {
        return $this->guard->withinIdentityThenCatalog(function () use ($receptionId): array {
            $reception = MessageReception::query()->where('id', $receptionId)->lockForUpdate()->first();

            if (
                $reception === null
                || $reception->consommateur !== RecordCatalogueWork::CONSUMER
                || ! in_array($reception->producteur, ['A5', 'A6'], true)
            ) {
                throw CatalogueConsultationException::absent();
            }

            if ($reception->traite_a !== null) {
                return [
                    'niveau_acquittement' => $reception->niveau_acquittement,
                    'reference_resultat' => $reception->reference_resultat,
                ];
            }

            $data = is_array($reception->donnees_minimales) ? $reception->donnees_minimales : [];
            $lineId = is_string($data['ligne_id'] ?? null) ? $data['ligne_id'] : null;
            $instructionId = is_string($data['instruction_id'] ?? null) ? $data['instruction_id'] : null;

            if (($lineId === null) === ($instructionId === null)) {
                throw CatalogueConsultationException::invalidInput('cible', 'La cause doit désigner une ligne ou une instruction.');
            }

            $effect = $lineId !== null
                ? $this->confirmLine($lineId, (string) $reception->evenement_externe_id)
                : $this->instructionEffect($instructionId);
            $workId = $lineId !== null ? $this->workFromLine($lineId) : $this->workFromInstruction($instructionId);

            if ($effect === 'masquer_avis' && is_string($workId)) {
                $this->aggregates->apply($workId);
            }

            $reception->traite_a = now();
            $reception->niveau_acquittement = 'engage_local';
            $reception->reference_resultat = $workId ?? $lineId ?? $instructionId;
            $reception->save();

            return [
                'niveau_acquittement' => 'engage_local',
                'reference_resultat' => $reception->reference_resultat,
            ];
        });
    }

    private function confirmLine(string $lineId, string $eventId): string
    {
        $line = DB::table('lignes_ordres_retrait as ligne')
            ->join('portees_decisions_mesures as portee', 'portee.id', '=', 'ligne.portee_id')
            ->where('ligne.id', $lineId)
            ->first(['ligne.id', 'portee.effet']);

        if ($line === null || ! is_string($line->effet)) {
            throw CatalogueConsultationException::absent();
        }

        $effective = $this->opposing('ligne_creatrice_id', $lineId);
        $confirmation = EffectConfirmation::query()
            ->where('ligne_id', $lineId)
            ->where('autorite', 'A2')
            ->lockForUpdate()
            ->first();

        if ($confirmation === null) {
            $confirmation = new EffectConfirmation([
                'id' => IdentityUlid::make(),
                'ligne_id' => $lineId,
                'autorite' => 'A2',
                'reference_demande' => $eventId,
                'statut' => $effective ? 'effet_confirme' : 'engage_local',
                'resultat' => ['effet' => $line->effet],
            ]);
            $confirmation->recu_instruction_a = now();
            $confirmation->applique_a = $effective ? now() : null;
            $confirmation->save();
        } elseif ($confirmation->applique_a === null && $effective) {
            $confirmation->statut = 'effet_confirme';
            $confirmation->applique_a = now();
            $confirmation->save();
        }

        return $line->effet;
    }

    private function instructionEffect(string $instructionId): string
    {
        $instruction = DB::table('instructions_retrait_publication')->where('id', $instructionId)->first();

        if ($instruction === null || ! is_string($instruction->effet)) {
            throw CatalogueConsultationException::absent();
        }

        return $instruction->effet;
    }

    private function workFromLine(string $lineId): ?string
    {
        $target = DB::table('lignes_ordres_retrait as ligne')
            ->join('resolutions_cibles as res', 'res.id', '=', 'ligne.resolution_id')
            ->where('ligne.id', $lineId)
            ->first(['res.oeuvre_id', 'res.avis_id']);

        if ($target === null) {
            return null;
        }

        if (is_string($target->oeuvre_id) && $target->oeuvre_id !== '') {
            return $target->oeuvre_id;
        }

        if (! is_string($target->avis_id) || $target->avis_id === '') {
            return null;
        }

        $workId = DB::table('avis')->where('avis_id', $target->avis_id)->value('oeuvre_id');

        return is_string($workId) ? $workId : null;
    }

    private function workFromInstruction(string $instructionId): ?string
    {
        $workId = DB::table('instructions_retrait_publication')->where('id', $instructionId)->value('oeuvre_id');

        return is_string($workId) && $workId !== '' ? $workId : null;
    }

    private function opposing(string $column, string $id): bool
    {
        return DB::table('restrictions_conformite as r')
            ->where('r.'.$column, $id)
            ->whereRaw('r.debut <= UTC_TIMESTAMP(6)')
            ->where(function ($window): void {
                $window->whereNull('r.fin')->orWhereRaw('UTC_TIMESTAMP(6) < r.fin');
            })
            ->whereNotExists(function ($lift): void {
                $lift->selectRaw('1')
                    ->from('levees_restrictions as l')
                    ->whereColumn('l.restriction_id', 'r.id')
                    ->whereRaw('l.effet_a <= UTC_TIMESTAMP(6)');
            })
            ->exists();
    }
}
