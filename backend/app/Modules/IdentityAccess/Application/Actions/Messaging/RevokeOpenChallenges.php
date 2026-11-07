<?php

namespace App\Modules\IdentityAccess\Application\Actions\Messaging;

use App\Modules\IdentityAccess\Domain\Enums\ChallengePurpose;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;

class RevokeOpenChallenges
{
    public function __construct(private CancelPendingTransmissions $cancelPending) {}

    public function __invoke(string $accountId, ChallengePurpose $purpose, ?string $exceptId = null): void
    {
        $query = VerificationChallenge::query()
            ->where('compte_id', $accountId)
            ->where('finalite', $purpose)
            ->whereNull('consomme_a')
            ->whereNull('revoque_a');

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $ids = $query->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        VerificationChallenge::query()
            ->whereIn('id', $ids)
            ->update(['revoque_a' => now()]);

        $this->cancelPending->forChallenges($ids);
    }
}
