<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\OrganizationMembership;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationDossier;

class IsIndependentReviewer
{
    public function __invoke(Account $account, VerificationDossier $dossier): bool
    {
        if ($dossier->deposant_id === $account->id) {
            return false;
        }

        $subject = $dossier->subject;

        if ($subject === null) {
            return false;
        }

        if ($subject->profil_compte_id === $account->id) {
            return false;
        }

        if (! is_string($subject->organisation_id) || $subject->organisation_id === '') {
            return true;
        }

        return ! OrganizationMembership::query()
            ->where('compte_id', $account->id)
            ->where('organisation_id', $subject->organisation_id)
            ->where('statut', 'active')
            ->exists();
    }
}
