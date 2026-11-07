<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Actions\Messaging\RetryTransmission;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;

class ListRetryableTransmissions
{
    public function __construct(
        private HasInternalPermission $hasPermission,
        private RetryTransmission $retry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $actor): array
    {
        if (! ($this->hasPermission)($actor, 'reprendre_transmissions')) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $rows = MessageTransmission::query()
            ->with('message')
            ->whereIn('etat', ['failed', 'retry_wait'])
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (MessageTransmission $transmission): array => $this->retry->serialize($transmission))
            ->values()
            ->all();

        return [
            'transmissions' => $rows,
        ];
    }
}
