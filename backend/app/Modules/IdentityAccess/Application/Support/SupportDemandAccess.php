<?php

namespace App\Modules\IdentityAccess\Application\Support;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\SupportRequest;

final class SupportDemandAccess
{
    public function __construct(
        public SupportRequest $demande,
        public string $role,
        public bool $canMutate,
        public bool $seeInternal,
    ) {}

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isClaimant(): bool
    {
        return $this->role === 'claimant';
    }
}
