<?php

namespace App\Modules\IdentityAccess\Application\Actions\Messaging;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;

class CancelPendingTransmissions
{
    /**
     * @param  iterable<int, string>  $challengeIds
     */
    public function forChallenges(iterable $challengeIds): void
    {
        $ids = collect($challengeIds)->filter()->values()->all();

        if ($ids === []) {
            return;
        }

        MessageTransmission::query()
            ->whereIn('etat', ['queued', 'retry_wait'])
            ->whereHas('message', function ($query) use ($ids): void {
                $query->whereIn('defi_id', $ids);
            })
            ->update(['etat' => 'cancelled']);
    }
}
