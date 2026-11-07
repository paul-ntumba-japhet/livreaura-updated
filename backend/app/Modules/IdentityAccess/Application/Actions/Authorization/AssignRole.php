<?php

namespace App\Modules\IdentityAccess\Application\Actions\Authorization;

use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AccountRole;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Role;

class AssignRole
{
    public function __invoke(
        Account $account,
        string $roleCode,
        string $origin,
        $now,
        ?string $attributedBy = null,
    ): AccountRole {
        $role = Role::query()->where('code', $roleCode)->lockForUpdate()->first();

        if ($role === null) {
            throw new IdentityAccessException(
                'service_indisponible',
                'Le referentiel des roles est indisponible.',
                503,
            );
        }

        $existing = AccountRole::query()
            ->where('compte_id', $account->id)
            ->where('role_id', $role->id)
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            if ($existing->statut !== 'active') {
                $existing->statut = 'active';
                $existing->origine = $origin;
                $existing->attribue_a = $now;
                $existing->revoque_a = null;
                $existing->motif = null;
                $existing->attribue_par = $attributedBy;
                $existing->save();
            }

            return $existing;
        }

        $assignment = new AccountRole;
        $assignment->id = IdentityUlid::make();
        $assignment->compte_id = $account->id;
        $assignment->role_id = $role->id;
        $assignment->statut = 'active';
        $assignment->origine = $origin;
        $assignment->attribue_a = $now;
        $assignment->attribue_par = $attributedBy;
        $assignment->save();

        return $assignment;
    }
}
