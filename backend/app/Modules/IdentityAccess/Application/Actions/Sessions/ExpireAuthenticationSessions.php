<?php

namespace App\Modules\IdentityAccess\Application\Actions\Sessions;

use App\Modules\IdentityAccess\Domain\Enums\SessionStatus;
use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\AuthenticationSession;

class ExpireAuthenticationSessions
{
    public function __construct(
        private CoordinationGuard $coordination,
        private RecordSessionAccessChange $recordChange,
    ) {}

    /**
     * @return list<string>
     */
    public function __invoke(int $limit = 50): array
    {
        $now = now();
        $ids = AuthenticationSession::query()
            ->where('statut', SessionStatus::Active)
            ->where(function ($query) use ($now): void {
                $query->where('expire_absolue_a', '<=', $now)
                    ->orWhere('expire_inactivite_a', '<=', $now);
            })
            ->orderBy('expire_absolue_a')
            ->orderBy('id')
            ->limit(max($limit, 1))
            ->pluck('id')
            ->all();

        $changed = [];

        foreach ($ids as $id) {
            $expired = $this->coordination->withinIdentity(function () use ($id): ?string {
                $session = AuthenticationSession::query()->where('id', $id)->lockForUpdate()->first();
                $now = now();

                if (
                    $session === null
                    || $session->statut !== SessionStatus::Active
                    || ($session->expire_absolue_a > $now && $session->expire_inactivite_a > $now)
                ) {
                    return null;
                }

                $session->statut = SessionStatus::Expired;
                $session->motif_revocation = 'Echeance depassee.';
                $session->save();

                $correlationId = IdentityUlid::make();
                $deadline = $session->expire_absolue_a?->toIso8601String()
                    .'|'.($session->expire_inactivite_a?->toIso8601String() ?? '');
                $idempotencyKey = 'a1-21:expiration:'.$session->id.':'.$deadline;

                ($this->recordChange)(
                    $session->compte_id,
                    'A1-21',
                    'Expiration de session.',
                    $correlationId,
                    $idempotencyKey,
                    SecretHasher::hmac($idempotencyKey),
                    [
                        'session_id' => $session->id,
                        'statut' => SessionStatus::Expired->value,
                    ],
                    [
                        'compte_id' => $session->compte_id,
                        'session_id' => $session->id,
                        'statut' => SessionStatus::Expired->value,
                    ],
                );

                return $session->id;
            });

            if (is_string($expired)) {
                $changed[] = $expired;
            }
        }

        return $changed;
    }
}
