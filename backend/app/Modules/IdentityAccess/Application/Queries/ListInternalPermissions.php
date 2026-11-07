<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalPermission;

class ListInternalPermissions
{
    /**
     * @return list<string>
     */
    public function __invoke(Account $account): array
    {
        $now = now();

        return InternalAttribution::query()
            ->with('permission')
            ->where('compte_id', $account->id)
            ->whereNull('revoque_a')
            ->where(function ($query) use ($now): void {
                $query->whereNull('expire_a')->orWhere('expire_a', '>', $now);
            })
            ->get()
            ->map(fn (InternalAttribution $attribution): ?string => $attribution->permission instanceof InternalPermission
                ? $attribution->permission->code
                : null)
            ->filter()
            ->values()
            ->all();
    }
}
