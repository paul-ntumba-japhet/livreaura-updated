<?php

namespace Tests\Feature\Modules\IdentityAccess;

use App\Modules\IdentityAccess\Domain\Support\IdentityUlid;
use App\Modules\IdentityAccess\Infrastructure\Notifications\MailIdentityNotificationSender;
use App\Modules\IdentityAccess\Infrastructure\Notifications\PasswordChangedMail;
use App\Modules\IdentityAccess\Infrastructure\Notifications\PasswordResetMail;
use App\Modules\IdentityAccess\Infrastructure\Notifications\VerifyEmailMail;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\Account;
use Illuminate\Support\Facades\Mail;

class MailIdentityNotificationSenderTest extends IdentityAccessTestCase
{
    public function test_activation_mail_uses_frontend_url_without_logging_the_secret(): void
    {
        Mail::fake();

        $account = Account::factory()->create();
        $challengeId = IdentityUlid::make();
        $secret = 'preuve-activation-test';

        $this->app->make(MailIdentityNotificationSender::class)
            ->sendEmailActivation($account, $challengeId, $secret);

        Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail) use ($account, $challengeId, $secret): bool {
            return $mail->hasTo($account->email_normalise)
                && str_contains($mail->actionUrl, '/inscription/activation')
                && str_contains($mail->actionUrl, 'defi_id='.$challengeId)
                && str_contains($mail->actionUrl, 'preuve='.rawurlencode($secret));
        });
    }

    public function test_password_reset_and_change_mails_are_addressed_to_the_account(): void
    {
        Mail::fake();

        $account = Account::factory()->active()->create();
        $challengeId = IdentityUlid::make();

        $sender = $this->app->make(MailIdentityNotificationSender::class);
        $sender->sendPasswordReset($account, $challengeId, 'preuve-reset-test');
        $sender->sendPasswordChanged($account);

        Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail) use ($account, $challengeId): bool {
            return $mail->hasTo($account->email_normalise)
                && str_contains($mail->actionUrl, '/recuperation')
                && str_contains($mail->actionUrl, 'defi_id='.$challengeId);
        });
        Mail::assertSent(PasswordChangedMail::class, function (PasswordChangedMail $mail) use ($account): bool {
            return $mail->hasTo($account->email_normalise);
        });
    }
}
