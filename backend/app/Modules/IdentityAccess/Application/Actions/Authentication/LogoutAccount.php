<?php

namespace App\Modules\IdentityAccess\Application\Actions\Authentication;

use App\Modules\IdentityAccess\Application\Actions\Sessions\ForgetTechnicalSession;
use App\Modules\IdentityAccess\Application\Actions\Sessions\RecordSessionAccessChange;
use App\Modules\IdentityAccess\Application\Actions\Sessions\RevokeSession;
use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Authentication\SessionIdentityBridge;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;
use App\Modules\IdentityAccess\Presentation\Http\Support\IdentityApiResponse;

class LogoutAccount
{
    public function __construct(
        private CoordinationGuard $coordination,
        private SessionIdentityBridge $sessions,
        private RevokeSession $revokeSession,
        private RecordSessionAccessChange $recordChange,
        private ForgetTechnicalSession $forgetSession,
    ) {}

    public function __invoke(): void
    {
        $session = $this->sessions->current();

        if ($session !== null) {
            $this->coordination->withinIdentity(function () use ($session): void {
                $locked = AuthenticationSession::query()->where('id', $session->id)->lockForUpdate()->first();

                if ($locked === null || $locked->statut !== SessionStatus::Active) {
                    return;
                }

                if (! ($this->revokeSession)($locked, 'Deconnexion.')) {
                    return;
                }

                $correlationId = IdentityApiResponse::correlationId();
                $idempotencyKey = 'a1-21:logout:'.$locked->id;

                ($this->recordChange)(
                    $locked->compte_id,
                    'A1-21',
                    'Deconnexion.',
                    $correlationId !== '' ? $correlationId : $locked->id,
                    $idempotencyKey,
                    SecretHasher::hmac($idempotencyKey),
                    [
                        'session_id' => $locked->id,
                        'statut' => SessionStatus::Revoked->value,
                    ],
                    [
                        'compte_id' => $locked->compte_id,
                        'session_id' => $locked->id,
                        'statut' => SessionStatus::Revoked->value,
                    ],
                );
            });
        }

        ($this->forgetSession)();
    }
}
