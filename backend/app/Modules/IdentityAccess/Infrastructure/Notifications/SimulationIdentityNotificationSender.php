<?php

namespace App\Modules\IdentityAccess\Infrastructure\Notifications;

use App\Modules\IdentityAccess\Domain\Contracts\IdentityNotificationSender;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Support\Facades\Log;

class SimulationIdentityNotificationSender implements IdentityNotificationSender
{
    /** @var list<array{account_id: string, challenge_id: ?string, secret: ?string, type: string}> */
    public array $sent = [];

    public function sendEmailActivation(Account $account, string $challengeId, string $secret): void
    {
        $this->record($account->id, $challengeId, $secret, 'email_activation');

        Log::info('identity.email_activation.sent', [
            'compte_id' => $account->id,
            'defi_id' => $challengeId,
        ]);
    }

    public function sendPasswordReset(Account $account, string $challengeId, string $secret): void
    {
        $this->record($account->id, $challengeId, $secret, 'password_reset');

        Log::info('identity.password_reset.sent', [
            'compte_id' => $account->id,
            'defi_id' => $challengeId,
        ]);
    }

    public function sendPasswordChanged(Account $account): void
    {
        $this->record($account->id, null, null, 'password_changed');

        Log::info('identity.password_changed.queued', [
            'compte_id' => $account->id,
        ]);
    }

    public function lastSecretFor(string $accountId, ?string $type = null): ?string
    {
        foreach (array_reverse($this->sent) as $item) {
            if ($item['account_id'] !== $accountId) {
                continue;
            }

            if ($type !== null && $item['type'] !== $type) {
                continue;
            }

            return $item['secret'];
        }

        return null;
    }

    public function lastChallengeIdFor(string $accountId, ?string $type = null): ?string
    {
        foreach (array_reverse($this->sent) as $item) {
            if ($item['account_id'] !== $accountId) {
                continue;
            }

            if ($type !== null && $item['type'] !== $type) {
                continue;
            }

            return $item['challenge_id'];
        }

        return null;
    }

    private function record(string $accountId, ?string $challengeId, ?string $secret, string $type): void
    {
        $this->sent[] = [
            'account_id' => $accountId,
            'challenge_id' => $challengeId,
            'secret' => $secret,
            'type' => $type,
        ];
    }
}
