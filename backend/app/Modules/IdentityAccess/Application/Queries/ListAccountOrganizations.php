<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationPermission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationType;

class ListAccountOrganizations
{
    public function __construct(
        private ListApplicableVerificationRequirements $requirements,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $account): array
    {
        $memberships = OrganizationMembership::query()
            ->with(['organization.type', 'permissions'])
            ->where('compte_id', $account->id)
            ->orderByDesc('cree_a')
            ->get();

        return [
            'organisations' => $memberships
                ->map(fn (OrganizationMembership $membership): ?array => $this->serializeMembership($membership))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function serializeMembership(OrganizationMembership $membership): ?array
    {
        $organization = $membership->organization;

        if (! $organization instanceof Organization) {
            return null;
        }

        $type = $organization->type;

        return [
            'id' => $organization->id,
            'raison_sociale' => $organization->raison_sociale,
            'pays' => $organization->pays,
            'ville' => $organization->ville,
            'statut_operationnel' => $organization->statut_operationnel,
            'type' => $type instanceof OrganizationType ? [
                'id' => $type->id,
                'code' => $type->code,
                'libelle' => $type->libelle,
            ] : null,
            'exigences' => ($this->requirements)->serializeMany(
                ($this->requirements)->forOrganizationType(
                    $organization->type_organisation_id,
                    (string) $organization->pays,
                ),
            ),
            'adhesion' => [
                'id' => $membership->id,
                'principal' => (bool) $membership->principal,
                'statut' => $membership->statut,
                'fonction' => $membership->fonction,
                'permissions' => $membership->permissions
                    ->map(fn (OrganizationPermission $permission): string => $permission->code)
                    ->values()
                    ->all(),
            ],
            'demande' => null,
        ];
    }
}
