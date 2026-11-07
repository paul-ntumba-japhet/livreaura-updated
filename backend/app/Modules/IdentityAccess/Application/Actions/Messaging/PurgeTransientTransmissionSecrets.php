<?php

namespace App\Modules\IdentityAccess\Application\Actions\Messaging;

use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\MessageTransmission;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\TransmissionSecret;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;
use Illuminate\Support\Facades\DB;

class PurgeTransientTransmissionSecrets
{
    /**
     * @return list<string>
     */
    public function __invoke(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        $now = now();
        $purged = [];

        DB::transaction(function () use ($limit, $now, &$purged): void {
            $rows = MessageTransmission::query()
                ->whereNotNull('contenu_chiffre_temporaire')
                ->whereNull('contenu_purge_a')
                ->where(function ($query) use ($now): void {
                    $query->whereIn('etat', ['sent', 'acked', 'failed', 'cancelled'])
                        ->orWhere(function ($expired): void {
                            $expired->whereNotNull('contenu_expire_a')
                                ->where('contenu_expire_a', '<=', now());
                        });
                })
                ->orderBy('id')
                ->limit($limit)
                ->lock('for update skip locked')
                ->get();

            foreach ($rows as $row) {
                $row->contenu_chiffre_temporaire = null;
                $row->contenu_purge_a = $now;
                $row->save();
                $purged[] = $row->id;
            }
        });

        return $purged;
    }

    /**
     * @param  iterable<int, string>  $challengeIds
     * @return list<string>
     */
    public function forChallenges(iterable $challengeIds): array
    {
        $ids = collect($challengeIds)->filter()->values()->all();

        if ($ids === []) {
            return [];
        }

        $secretTransmissionIds = TransmissionSecret::query()
            ->whereIn('defi_id', $ids)
            ->pluck('transmission_id');

        $purged = [];

        DB::transaction(function () use ($secretTransmissionIds, &$purged): void {
            $rows = MessageTransmission::query()
                ->whereIn('id', $secretTransmissionIds)
                ->whereNotNull('contenu_chiffre_temporaire')
                ->whereNull('contenu_purge_a')
                ->lockForUpdate()
                ->get();

            $now = now();

            foreach ($rows as $row) {
                $row->contenu_chiffre_temporaire = null;
                $row->contenu_purge_a = $now;
                $row->save();
                $purged[] = $row->id;
            }
        });

        return $purged;
    }

    /**
     * @return list<string>
     */
    public function forExpiredChallenges(int $limit = 50): array
    {
        $ids = VerificationChallenge::query()
            ->where('expire_a', '<=', now())
            ->whereNull('consomme_a')
            ->orderBy('expire_a')
            ->limit(max(1, min($limit, 200)))
            ->pluck('id');

        return $this->forChallenges($ids);
    }
}
