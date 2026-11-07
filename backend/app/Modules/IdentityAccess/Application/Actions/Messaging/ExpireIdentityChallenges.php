<?php

namespace App\Modules\IdentityAccess\Application\Actions\Messaging;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;

class ExpireIdentityChallenges
{
    public function __construct(
        private CancelPendingTransmissions $cancel,
        private PurgeTransientTransmissionSecrets $purge,
    ) {}

    /**
     * @return list<string>
     */
    public function __invoke(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        $ids = VerificationChallenge::query()
            ->where('expire_a', '<=', now())
            ->whereNull('consomme_a')
            ->whereNull('revoque_a')
            ->orderBy('expire_a')
            ->limit($limit)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        $this->cancel->forChallenges($ids);
        $this->purge->forChallenges($ids);

        return $ids->map(fn ($id): string => (string) $id)->all();
    }
}
