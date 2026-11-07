<?php

namespace App\Modules\IdentityAccess\Infrastructure\Persistence;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CoordinationGuard
{
    public function withinIdentity(callable $callback): mixed
    {
        return $this->withinBarriers(['identite'], $callback);
    }

    public function withinIdentityThenCatalog(callable $callback): mixed
    {
        return $this->withinBarriers(['identite', 'catalogue'], $callback);
    }

    public function withinIdentityThenConservation(callable $callback): mixed
    {
        return $this->withinBarriers(['identite', 'conservation'], $callback);
    }

    /**
     * @param  list<string>  $codes
     */
    private function withinBarriers(array $codes, callable $callback): mixed
    {
        if (DB::transactionLevel() > 0) {
            $this->lockBarriers($codes);

            return $callback();
        }

        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                DB::statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');

                return DB::transaction(function () use ($callback, $codes) {
                    $this->lockBarriers($codes);

                    return $callback();
                });
            } catch (QueryException $exception) {
                if ($attempts >= 3 || ! $this->isRetryable($exception)) {
                    throw $exception;
                }

                usleep(random_int(10_000, 80_000));
            }
        }
    }

    /**
     * @param  list<string>  $codes
     */
    private function lockBarriers(array $codes): void
    {
        foreach ($codes as $code) {
            DB::table('verrous_coordination')->where('code', $code)->lockForUpdate()->first();
        }
    }

    private function isRetryable(QueryException $exception): bool
    {
        $sqlState = (string) $exception->errorInfo[0];

        return in_array($sqlState, ['40001', 'HY000'], true)
            || str_contains($exception->getMessage(), 'Deadlock')
            || str_contains($exception->getMessage(), 'Lock wait timeout');
    }
}
