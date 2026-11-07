<?php

namespace App\Modules\IdentityAccess\Application\Queries;

use App\Modules\IdentityAccess\Application\Actions\Configuration\ApproveServiceConfiguration;
use App\Modules\IdentityAccess\Application\Support\HasInternalPermission;
use App\Modules\IdentityAccess\Domain\Exceptions\IdentityAccessException;
use App\Modules\IdentityAccess\Infrastructure\Persistence\CurrentServiceConfiguration;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceConfiguration;

class ListServiceConfigurations
{
    public function __construct(
        private HasInternalPermission $hasPermission,
        private CurrentServiceConfiguration $current,
        private ApproveServiceConfiguration $approve,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Account $actor, ?string $code): array
    {
        if (! ($this->hasPermission)($actor, 'configurer_service')) {
            throw new IdentityAccessException(
                'permission_insuffisante',
                'Cette operation interne n est pas autorisee.',
                403,
            );
        }

        $codes = $code !== null && $code !== ''
            ? [$code]
            : ApproveServiceConfiguration::CODES;

        $groups = [];

        foreach ($codes as $item) {
            if (! in_array($item, ApproveServiceConfiguration::CODES, true)) {
                throw new IdentityAccessException(
                    'saisie_invalide',
                    'Ce code de configuration n est pas reconnu.',
                    422,
                    ['code' => ['Code inconnu.']],
                );
            }

            $current = $this->current->find($item);
            $versions = ServiceConfiguration::query()
                ->where('code', $item)
                ->orderByDesc('version')
                ->limit(20)
                ->get()
                ->map(function (ServiceConfiguration $configuration) use ($current): array {
                    $serialized = $this->approve->serialize($configuration);
                    $serialized['courante'] = $current !== null && $current->id === $configuration->id;

                    return $serialized;
                })
                ->values()
                ->all();

            $groups[] = [
                'code' => $item,
                'courante' => $current === null ? null : $this->approve->serialize($current),
                'versions' => $versions,
            ];
        }

        return [
            'configurations' => $groups,
        ];
    }
}
