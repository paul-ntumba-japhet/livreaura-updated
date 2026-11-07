<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Domain\Enums\AccountStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\KycSubject;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;

class CanDepositVerification
{
    public function __invoke(Account $account, KycSubject $subject): bool
    {
        return $this->forSubject($account, $subject);
    }

    public function forSubject(Account $account, KycSubject $subject): bool
    {
        if ($account->statut !== AccountStatus::Active) {
            return false;
        }

        if ($subject->profil_compte_id === $account->id) {
            return true;
        }

        if (! is_string($subject->organisation_id) || $subject->organisation_id === '') {
            return false;
        }

        $membership = OrganizationMembership::query()
            ->with('permissions')
            ->where('compte_id', $account->id)
            ->where('organisation_id', $subject->organisation_id)
            ->where('statut', 'active')
            ->first();

        if ($membership === null) {
            return false;
        }

        return $membership->permissions->contains(fn ($permission): bool => $permission->code === 'deposer_kyc');
    }
}
