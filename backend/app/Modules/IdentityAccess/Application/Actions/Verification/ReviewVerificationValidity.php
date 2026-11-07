<?php

namespace App\Modules\IdentityAccess\Application\Actions\Verification;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Domain\Support\SecretHasher;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CoordinationGuard;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationCapability;

class ReviewVerificationValidity
{
    public function __construct(
        private CoordinationGuard $coordination,
        private ChangeVerificationCapabilityState $changeState,
    ) {}

    /**
     * @return list<string>
     */
    public function __invoke(int $limit = 50): array
    {
        $ids = VerificationCapability::query()
            ->whereIn('etat', ['valid', 'a_renouveler'])
            ->whereNotNull('valide_jusqua')
            ->where('valide_jusqua', '<=', now())
            ->orderBy('valide_jusqua')
            ->orderBy('id')
            ->limit(max($limit, 1))
            ->pluck('id')
            ->all();

        $changed = [];

        foreach ($ids as $id) {
            $result = $this->coordination->withinIdentity(function () use ($id): ?string {
                $capability = VerificationCapability::query()->where('id', $id)->lockForUpdate()->first();

                if (
                    $capability === null
                    || ! in_array($capability->etat, ['valid', 'a_renouveler'], true)
                    || $capability->valide_jusqua === null
                    || $capability->valide_jusqua->gt(now())
                ) {
                    return null;
                }

                $deadline = $capability->valide_jusqua->toIso8601String();
                $idempotencyKey = 'a1-14:expiration:'.$capability->id.':'.$deadline;
                $intention = SecretHasher::hmac(json_encode([
                    'habilitation_id' => $capability->id,
                    'action' => 'expiration',
                    'valide_jusqua' => $deadline,
                ], JSON_THROW_ON_ERROR));

                ($this->changeState)(
                    $capability,
                    'expired',
                    'Echeance depassee. Les acces courants sont refuses.',
                    'expiration',
                    $idempotencyKey,
                    $intention,
                    IdentityUlid::make(),
                    null,
                );

                return $capability->id;
            });

            if (is_string($result)) {
                $changed[] = $result;
            }
        }

        return $changed;
    }
}
