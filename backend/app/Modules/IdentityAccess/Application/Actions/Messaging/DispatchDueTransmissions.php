<?php

namespace App\Modules\IdentityAccess\Application\Actions\Messaging;

use App\Modules\IdentityAccess\Application\Jobs\SendIdentityNotificationJob;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use Illuminate\Support\Facades\DB;

class DispatchDueTransmissions
{
    public const int BATCH_SIZE = 50;

    public const int BAIL_SECONDS = 60;

    /**
     * @return list<string>
     */
    public function __invoke(int $limit = self::BATCH_SIZE, bool $sendInline = false): array
    {
        $limit = max(1, min($limit, 200));
        $owner = $this->owner();
        $claimed = [];

        DB::transaction(function () use ($limit, $owner, &$claimed): void {
            $rows = MessageTransmission::query()
                ->where('canal', 'email')
                ->where(function ($query): void {
                    $query->where(function ($queued): void {
                        $queued->whereIn('etat', ['queued', 'retry_wait'])
                            ->where(function ($due): void {
                                $due->whereNull('prochaine_tentative_a')
                                    ->orWhere('prochaine_tentative_a', '<=', now());
                            });
                    })->orWhere(function ($expired): void {
                        $expired->where('etat', 'processing')
                            ->whereNotNull('bail_jusqua')
                            ->where('bail_jusqua', '<', now());
                    });
                })
                ->orderBy('id')
                ->limit($limit)
                ->lock('for update skip locked')
                ->get();

            foreach ($rows as $row) {
                if (
                    in_array($row->etat, ['queued', 'retry_wait'], true)
                    && $row->prochaine_tentative_a !== null
                    && $row->prochaine_tentative_a->isFuture()
                ) {
                    continue;
                }

                $row->etat = 'processing';
                $row->bail_jusqua = now()->addSeconds(self::BAIL_SECONDS);
                $row->pris_par = $owner;
                $row->save();
                $claimed[] = $row->id;
            }
        });

        foreach ($claimed as $transmissionId) {
            if ($sendInline) {
                SendIdentityNotificationJob::dispatchSync($transmissionId);
            } else {
                SendIdentityNotificationJob::dispatch($transmissionId);
            }
        }

        return $claimed;
    }

    private function owner(): string
    {
        return sprintf(
            'a1-dispatch:%s:%d:%s',
            (string) gethostname(),
            getmypid(),
            bin2hex(random_bytes(4)),
        );
    }
}
