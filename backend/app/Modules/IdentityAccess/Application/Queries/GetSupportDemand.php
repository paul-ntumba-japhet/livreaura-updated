<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Support\ResolveSupportDemandAccess;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportEvidence;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportExchange;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;

class GetSupportDemand
{
    public function __construct(
        private ResolveSupportDemandAccess $resolveAccess,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $actor, string $demandKey): array
    {
        $access = ($this->resolveAccess)($actor, $demandKey);

        return $this->serialize($access->demande, $access->seeInternal, $access->canMutate, $access->isAgent());
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(SupportRequest $demande, bool $seeInternal, bool $canMutate, bool $agent): array
    {
        $exchanges = SupportExchange::query()
            ->where('demande_id', $demande->id)
            ->when(! $seeInternal, fn ($query) => $query->where('visibilite', 'demandeur'))
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map(fn (SupportExchange $exchange): array => $this->serializeExchange($exchange))
            ->values()
            ->all();

        $pieces = SupportEvidence::query()
            ->where('demande_id', $demande->id)
            ->when(! $seeInternal, fn ($query) => $query->where('acces', 'demandeur'))
            ->orderBy('depose_a')
            ->orderBy('id')
            ->get()
            ->map(fn (SupportEvidence $piece): array => $this->serializeEvidence($piece))
            ->values()
            ->all();

        return [
            'demande' => [
                'id' => $demande->id,
                'reference' => $demande->reference,
                'type' => $demande->type,
                'identite_etat' => $demande->identite_etat,
                'clos_a' => $demande->clos_a?->toIso8601String(),
            ],
            'echanges' => $exchanges,
            'pieces' => $pieces,
            'capacites' => [
                'repondre' => $canMutate,
                'deposer' => $canMutate,
                'notes_internes' => $agent && $canMutate,
                'consulter_internes' => $seeInternal,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeExchange(SupportExchange $exchange): array
    {
        return [
            'id' => $exchange->id,
            'origine' => $exchange->origine,
            'visibilite' => $exchange->visibilite,
            'contenu' => $exchange->contenu_prive,
            'date' => $exchange->date?->toIso8601String(),
            'canal' => $exchange->canal,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeEvidence(SupportEvidence $piece): array
    {
        return [
            'id' => $piece->id,
            'type' => $piece->type,
            'acces' => $piece->acces,
            'echange_id' => $piece->echange_id,
            'depose_a' => $piece->depose_a?->toIso8601String(),
        ];
    }
}
