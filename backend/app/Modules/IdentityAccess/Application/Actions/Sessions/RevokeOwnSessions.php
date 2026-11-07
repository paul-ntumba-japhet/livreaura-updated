<?php

namespace App\Modules\IdentityAccess\Application\Actions\Sessions;

use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;

class RevokeOwnSessions
{
    public function __construct(
        private CoordinationGuard $coordination,
        private SessionIdentityBridge $sessions,
        private RevokeAccountSessions $revokeSessions,
        private RecordSessionAccessChange $recordChange,
        private ForgetTechnicalSession $forgetSession,
    ) {}

    public function __invoke(): void
    {
        $session = $this->sessions->current();

        if ($session !== null) {
            $this->coordination->withinIdentity(function () use ($session): void {
                $revoked = ($this->revokeSessions)($session->compte_id, 'Deconnexion de toutes les sessions.');

                if ($revoked < 1) {
                    return;
                }

                $correlationId = IdentityApiResponse::correlationId();
                $idempotencyKey = 'a1-21:revoke-all:'.$session->compte_id.':'.$session->id;

                ($this->recordChange)(
                    $session->compte_id,
                    'A1-21',
                    'Deconnexion de toutes les sessions.',
                    $correlationId !== '' ? $correlationId : $session->id,
                    $idempotencyKey,
                    SecretHasher::hmac($idempotencyKey.':'.$revoked),
                    [
                        'sessions_revoquees' => $revoked,
                        'statut' => 'revoked',
                    ],
                    [
                        'compte_id' => $session->compte_id,
                        'sessions_revoquees' => $revoked,
                    ],
                );
            });
        }

        ($this->forgetSession)();
    }
}
