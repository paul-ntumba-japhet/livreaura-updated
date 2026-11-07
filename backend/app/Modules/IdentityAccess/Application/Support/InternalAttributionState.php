<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalPermission;

class InternalAttributionState
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(InternalAttribution $attribution): array
    {
        $permission = $attribution->permission;
        $code = $permission instanceof InternalPermission ? $permission->code : '';
        $label = $permission instanceof InternalPermission ? $permission->libelle : '';
        $now = now();
        $etat = 'effective';

        if ($attribution->revoque_a !== null) {
            $etat = 'revoquee';
        } elseif ($attribution->expire_a !== null && $attribution->expire_a->lte($now)) {
            $etat = 'expiree';
        } elseif ($attribution->attribue_a !== null && $attribution->attribue_a->gt($now)) {
            $etat = 'a_venir';
        }

        return [
            'id' => $attribution->id,
            'permission' => $code,
            'libelle' => $label,
            'etat' => $etat,
            'attribue_a' => $attribution->attribue_a?->toIso8601String(),
            'expire_a' => $attribution->expire_a?->toIso8601String(),
            'revoque_a' => $attribution->revoque_a?->toIso8601String(),
            'motif' => $attribution->motif,
        ];
    }
}
