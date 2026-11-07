<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Application\Support\InternalAttributionState;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalPermission;

class ListInternalPermissionCatalog
{
    public function __construct(
        private HasInternalPermission $hasPermission,
        private InternalAttributionState $state,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $actor, ?string $accountId): array
    {
        if (! ($this->hasPermission)($actor, 'gerer_habilitations_internes')) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $permissions = InternalPermission::query()
            ->orderBy('code')
            ->get()
            ->map(fn (InternalPermission $permission): array => [
                'code' => $permission->code,
                'libelle' => $permission->libelle,
            ])
            ->values()
            ->all();

        $attributions = [];
        $compte = null;

        if (is_string($accountId) && $accountId !== '') {
            $accountId = strtoupper($accountId);

            if (! IdentityUlid::isValid($accountId)) {
                throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
            }

            $target = Account::query()->where('id', $accountId)->first();

            if ($target === null) {
                throw new IdentityAccessException('compte_inconnu', 'Ce compte n est pas visible.', 404);
            }

            $compte = ['id' => $target->id, 'statut' => $target->statut->value];
            $attributions = InternalAttribution::query()
                ->with('permission')
                ->where('compte_id', $target->id)
                ->orderByDesc('attribue_a')
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(fn (InternalAttribution $attribution): array => ($this->state)($attribution))
                ->values()
                ->all();
        }

        return [
            'permissions' => $permissions,
            'compte' => $compte,
            'attributions' => $attributions,
        ];
    }
}
