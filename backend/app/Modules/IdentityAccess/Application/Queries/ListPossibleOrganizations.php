<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Domain\Support\OrganizationName;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationType;

class ListPossibleOrganizations
{
    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(string $typeId, string $legalName, string $country): array
    {
        $normalized = OrganizationName::normalize($legalName);

        if ($normalized === '' || ! OrganizationType::query()->where('id', $typeId)->exists()) {
            return [];
        }

        return Organization::query()
            ->with('type')
            ->where('type_organisation_id', $typeId)
            ->where('pays', strtoupper($country))
            ->whereIn('statut_operationnel', ['active', 'restricted'])
            ->get()
            ->filter(fn (Organization $organization): bool => OrganizationName::normalize($organization->raison_sociale) === $normalized)
            ->values()
            ->map(fn (Organization $organization): array => $this->serialize($organization))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(Organization $organization): array
    {
        $type = $organization->type;

        return [
            'id' => $organization->id,
            'raison_sociale' => $organization->raison_sociale,
            'pays' => $organization->pays,
            'ville' => $organization->ville,
            'statut_operationnel' => $organization->statut_operationnel,
            'type' => $type === null ? null : [
                'id' => $type->id,
                'code' => $type->code,
                'libelle' => $type->libelle,
            ],
        ];
    }
}
