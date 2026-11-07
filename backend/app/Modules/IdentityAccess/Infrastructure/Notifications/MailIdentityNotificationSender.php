<?php

namespace App\Modules\IdentityAccess\Infrastructure\Notifications;

use App\Modules\IdentityAccess\Domain\Contracts\IdentityNotificationSender;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\VerificationChallenge;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailIdentityNotificationSender implements IdentityNotificationSender
{
    public function sendEmailActivation(Account $account, string $challengeId, string $secret): void
    {
        Mail::to($account->email_normalise)->send(new VerifyEmailMail(
            $this->actionUrl(config('identity.activation_path'), $challengeId, $secret),
            $this->expiresAtLabel($challengeId),
        ));

        Log::info('identity.email_activation.sent', [
            'compte_id' => $account->id,
            'defi_id' => $challengeId,
        ]);
    }

    public function sendPasswordReset(Account $account, string $challengeId, string $secret): void
    {
        Mail::to($account->email_normalise)->send(new PasswordResetMail(
            $this->actionUrl(config('identity.password_reset_path'), $challengeId, $secret),
            $this->expiresAtLabel($challengeId),
        ));

        Log::info('identity.password_reset.sent', [
            'compte_id' => $account->id,
            'defi_id' => $challengeId,
        ]);
    }

    public function sendPasswordChanged(Account $account): void
    {
        Mail::to($account->email_normalise)->send(new PasswordChangedMail);

        Log::info('identity.password_changed.queued', [
            'compte_id' => $account->id,
        ]);
    }

    private function actionUrl(string $path, string $challengeId, string $secret): string
    {
        $base = rtrim((string) config('app.frontend_url'), '/');
        $path = '/'.ltrim($path, '/');

        return $base.$path.'?'.http_build_query([
            'defi_id' => $challengeId,
            'preuve' => $secret,
        ]);
    }

    private function expiresAtLabel(string $challengeId): ?string
    {
        $expiresAt = VerificationChallenge::query()->find($challengeId)?->expire_a;

        if ($expiresAt === null) {
            return null;
        }

        return $expiresAt->utc()->format('d/m/Y H:i').' UTC';
    }
}
