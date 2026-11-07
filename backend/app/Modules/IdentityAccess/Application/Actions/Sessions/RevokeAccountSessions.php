<?php

namespace App\Modules\IdentityAccess\Application\Actions\Sessions;

use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;

class RevokeAccountSessions
{
    public function __invoke(string $accountId, string $motif): int
    {
        $now = now();
        $sessions = AuthenticationSession::query()
            ->where('compte_id', $accountId)
            ->where('statut', SessionStatus::Active)
            ->lockForUpdate()
            ->orderBy('id')
            ->get();

        $revoked = 0;

        foreach ($sessions as $session) {
            $session->statut = SessionStatus::Revoked;
            $session->revoque_a = $now;
            $session->motif_revocation = $motif;
            $session->save();
            $revoked++;
        }

        return $revoked;
    }
}
