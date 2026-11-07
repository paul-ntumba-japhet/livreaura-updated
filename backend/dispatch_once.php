<?php

use App\Modules\IdentityAccess\Application\Actions\Messaging\DispatchDueTransmissions;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Mail;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$out = [];
$out[] = 'driver='.config('identity.notification_driver');
$out[] = 'mailer='.config('mail.default');
$out[] = 'user='.config('mail.mailers.smtp.username');

try {
    Mail::raw('Test SMTP Livreaura', function ($message): void {
        $message->to((string) config('mail.from.address'))->subject('Test SMTP Livreaura');
    });
    $out[] = 'smtp_test=ok';
} catch (Throwable $e) {
    $out[] = 'smtp_test='.$e::class;
    $out[] = 'smtp_error='.preg_replace('/[^\x20-\x7E]/', ' ', $e->getMessage());
}

$claimed = $app->make(DispatchDueTransmissions::class)(20, true);
$out[] = 'claimed='.count($claimed);

file_put_contents(__DIR__.'/storage/logs/dispatch_once.out', implode(PHP_EOL, $out).PHP_EOL);
echo implode(PHP_EOL, $out).PHP_EOL;
