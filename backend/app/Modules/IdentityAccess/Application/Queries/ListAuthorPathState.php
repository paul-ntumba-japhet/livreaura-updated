<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\CatalogAuthor;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Role;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;

class ListAuthorPathState
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $account): array
    {
        $account->load(['accountRoles.role']);

        $hasAuthor = $account->accountRoles
            ->where('statut', 'active')
            ->contains(fn ($assignment): bool => $assignment->role instanceof Role && $assignment->role->code === 'author');

        $fiche = CatalogAuthor::query()->where('compte_id', $account->id)->first();
        $demande = SupportRequest::query()
            ->where('demandeur_compte_id', $account->id)
            ->where('type', 'revendication_auteur')
            ->whereNull('clos_a')
            ->orderByDesc('cree_a')
            ->first();

        $etat = 'non_active';

        if ($demande !== null) {
            $etat = 'revendication_en_examen';
        } elseif ($hasAuthor) {
            $etat = 'active';
        }

        return [
            'parcours_auteur' => [
                'etat' => $etat,
                'fiche' => $this->serializeFiche($fiche),
                'demande' => $demande === null ? null : [
                    'reference' => $demande->reference,
                ],
            ],
        ];
    }

    /**
     * @return array{id: string, nom_affiche: string, statut_publication: string}|null
     */
    public function serializeFiche(?CatalogAuthor $fiche): ?array
    {
        if ($fiche === null) {
            return null;
        }

        return [
            'id' => $fiche->auteur_id,
            'nom_affiche' => $fiche->nom_affiche,
            'statut_publication' => $fiche->statut_publication,
        ];
    }
}
