<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\LegalTextVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListRegistrationPaths
{
    public function __construct(
        private ListApplicableVerificationRequirements $requirements,
    ) {}

    /**
     * @return array{parcours: list<array<string, string>>, textes_requis: list<array<string, mixed>>, types_organisation: list<array<string, mixed>>, exigences_auteur: list<array<string, mixed>>}
     */
    public function __invoke(string $langue = 'fr'): array
    {
        return [
            'parcours' => [
                [
                    'code' => 'lecteur',
                    'libelle' => 'Lecteur',
                    'role_a_la_creation' => 'reader',
                ],
                [
                    'code' => 'auteur',
                    'libelle' => 'Auteur',
                    'role_a_la_creation' => 'reader',
                ],
                [
                    'code' => 'representant',
                    'libelle' => 'Représentant',
                    'role_a_la_creation' => 'reader',
                ],
            ],
            'textes_requis' => $this->requiredTexts($langue),
            'types_organisation' => $this->organizationTypes(),
            'exigences_auteur' => ($this->requirements)->serializeMany(
                ($this->requirements)->forPersonalCommercial('CD'),
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function organizationTypes(): array
    {
        $order = ['ecole', 'universite', 'bibliotheque', 'editeur', 'librairie', 'association'];

        return DB::table('types_organisation')
            ->get(['id', 'code', 'libelle'])
            ->sortBy(function ($row) use ($order): int {
                $index = array_search($row->code, $order, true);

                return $index === false ? 100 : $index;
            })
            ->values()
            ->map(fn ($row): array => [
                'id' => $row->id,
                'code' => $row->code,
                'libelle' => $row->libelle,
                'exigences' => ($this->requirements)->serializeMany(
                    ($this->requirements)->forOrganizationType($row->id, 'CD'),
                ),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function requiredTexts(string $langue): array
    {
        return $this->applicableRequired($langue)
            ->map(fn (LegalTextVersion $text): array => [
                'id' => $text->id,
                'type' => $text->type,
                'version' => $text->version,
                'langue' => $text->langue,
                'contenu' => $text->contenu,
                'reference_immuable' => $text->reference_immuable,
                'requis_pour_activation' => true,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, LegalTextVersion>
     */
    public function applicableRequired(string $langue): Collection
    {
        $texts = LegalTextVersion::query()
            ->where('requis_pour_activation', 1)
            ->whereIn('langue', array_unique([$langue, 'fr']))
            ->orderByDesc('publie_a')
            ->get();

        return $texts
            ->groupBy('type')
            ->map(function (Collection $versions) use ($langue): ?LegalTextVersion {
                return $versions->firstWhere('langue', $langue)
                    ?? $versions->firstWhere('langue', 'fr')
                    ?? $versions->first();
            })
            ->filter()
            ->values();
    }
}
