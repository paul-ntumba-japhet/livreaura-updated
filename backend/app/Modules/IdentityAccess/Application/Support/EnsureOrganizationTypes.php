<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationType;

class EnsureOrganizationTypes
{
    /**
     * @var list<array{code: string, libelle: string}>
     */
    public const DEFINITIONS = [
        ['code' => 'ecole', 'libelle' => 'École'],
        ['code' => 'universite', 'libelle' => 'Université'],
        ['code' => 'bibliotheque', 'libelle' => 'Bibliothèque'],
        ['code' => 'editeur', 'libelle' => 'Éditeur / maison d’édition'],
        ['code' => 'librairie', 'libelle' => 'Librairie'],
        ['code' => 'association', 'libelle' => 'Association'],
    ];

    public function __invoke(): void
    {
        foreach (self::DEFINITIONS as $definition) {
            $type = OrganizationType::query()->where('code', $definition['code'])->first();

            if ($type === null) {
                $type = new OrganizationType;
                $type->id = IdentityUlid::make();
                $type->code = $definition['code'];
                $type->libelle = $definition['libelle'];
                $type->save();
                continue;
            }

            if ($type->libelle !== $definition['libelle']) {
                $type->libelle = $definition['libelle'];
                $type->save();
            }
        }
    }
}
