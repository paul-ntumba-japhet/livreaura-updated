<?php

namespace App\Modules\IdentityAccess\Application\Actions\Organizations;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountEvent;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Organization;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationPermission;

class InstallInitialRepresentative
{
    public function __invoke(Account $account, Organization $organization, $now, string $correlationId): OrganizationMembership
    {
        $permission = OrganizationPermission::query()->where('code', 'deposer_kyc')->lockForUpdate()->first();

        if ($permission === null) {
            throw new IdentityAccessException(
                'service_indisponible',
                'La permission de depot organisationnel est indisponible.',
                503,
            );
        }

        $membership = new OrganizationMembership;
        $membership->id = IdentityUlid::make();
        $membership->compte_id = $account->id;
        $membership->organisation_id = $organization->id;
        $membership->fonction = 'Representant declarant';
        $membership->principal = 1;
        $membership->statut = 'active';
        $membership->origine = 'declaration_initiale';
        $membership->cree_a = $now;
        $membership->active_a = $now;
        $membership->save();

        $membership->permissions()->attach($permission->id);

        $event = new AccountEvent;
        $event->id = IdentityUlid::make();
        $event->compte_sujet_id = $account->id;
        $event->adhesion_id = $membership->id;
        $event->permission_id = $permission->id;
        $event->type = 'adhesion_organisation_modifiee';
        $event->date = $now;
        $event->nouvel_etat = json_encode([
            'organisation_id' => $organization->id,
            'principal' => true,
            'statut' => 'active',
            'permission' => 'deposer_kyc',
            'representant_verifie' => false,
        ], JSON_THROW_ON_ERROR);
        $event->motif = 'Installation du representant principal declarant, sans badge verifie.';
        $event->origine_systeme = 'A1-10a';
        $event->correlation = $correlationId;
        $event->save();

        return $membership;
    }
}
