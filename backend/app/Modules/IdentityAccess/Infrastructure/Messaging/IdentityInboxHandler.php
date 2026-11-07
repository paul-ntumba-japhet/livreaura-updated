<?php

namespace App\Modules\IdentityAccess\Infrastructure\Messaging;

use App\Modules\IdentityAccess\Application\Actions\Messaging\ResumeUntreatedReceptions;

class IdentityInboxHandler
{
    public function __construct(private ResumeUntreatedReceptions $resume) {}

    /**
     * @return list<string>
     */
    public function __invoke(int $limit = 50): array
    {
        return ($this->resume)($limit);
    }
}
