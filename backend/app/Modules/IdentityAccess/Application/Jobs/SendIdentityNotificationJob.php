<?php

namespace App\Modules\IdentityAccess\Application\Jobs;

use App\Modules\IdentityAccess\Application\Actions\Messaging\DeliverMessageTransmission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendIdentityNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public readonly string $transmissionId)
    {
        $this->onQueue('a1-critical');
    }

    public function handle(DeliverMessageTransmission $deliver): void
    {
        $deliver($this->transmissionId);
    }
}
