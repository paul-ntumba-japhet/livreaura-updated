<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\ServiceConfiguration;
use Illuminate\Support\Carbon;

class CurrentServiceConfiguration
{
    public function find(string $code): ?ServiceConfiguration
    {
        $now = Carbon::now();

        return ServiceConfiguration::query()
            ->where('code', $code)
            ->whereNotNull('effet_a')
            ->whereNotNull('approuve_a')
            ->where('effet_a', '<=', $now)
            ->orderByDesc('effet_a')
            ->orderByDesc('version')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function values(string $code): array
    {
        $configuration = $this->find($code);

        return is_array($configuration?->valeurs) ? $configuration->valeurs : [];
    }
}
