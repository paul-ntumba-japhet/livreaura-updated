<?php

namespace App\Modules\IdentityAccess\Application\Actions\Sessions;

use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;

class RevokeSession
{
    public function __invoke(AuthenticationSession $session, string $motif): bool
    {
        if ($session->statut !== SessionStatus::Active) {
            return false;
        }

        $session->statut = SessionStatus::Revoked;
        $session->revoque_a = now();
        $session->motif_revocation = $motif;
        $session->save();

        return true;
    }
}
