<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\InternalAttribution;

class HasInternalPermission
{
    public function __invoke(Account $account, string $code): bool
    {
        if ($account->statut !== AccountStatus::Active) {
            return false;
        }

        $now = now();

        return InternalAttribution::query()
            ->where('compte_id', $account->id)
            ->whereNull('revoque_a')
            ->where(function ($query) use ($now): void {
                $query->whereNull('expire_a')->orWhere('expire_a', '>', $now);
            })
            ->whereHas('permission', fn ($query) => $query->where('code', $code))
            ->exists();
    }
}
