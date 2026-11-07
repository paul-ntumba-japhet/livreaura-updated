<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Actions\Tasks\AssignServiceSubstitute;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceSubstitute;

class ListServiceSubstitutes
{
    public function __construct(
        private HasInternalPermission $hasPermission,
        private AssignServiceSubstitute $assign,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $actor): array
    {
        if (! ($this->hasPermission)($actor, 'affecter_taches')) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $rows = ServiceSubstitute::query()
            ->orderByDesc('defini_a')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (ServiceSubstitute $substitute): array => $this->assign->serialize($substitute))
            ->values()
            ->all();

        return [
            'suppleances' => $rows,
        ];
    }
}
