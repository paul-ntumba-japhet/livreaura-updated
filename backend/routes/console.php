<?php

use App\Modules\IdentityAccess\Presentation\Console\Commands\DispatchTransmissionsCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\ExpireIdentityChallengesCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\ExpireIdentitySessionsCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\MonitorServiceTasksCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\PurgeTransientSecretsCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\ResumeInboxCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\ReviewVerificationExpiriesCommand;
use App\Modules\IdentityAccess\Presentation\Console\Commands\SendVerificationRemindersCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(DispatchTransmissionsCommand::class)
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command(ResumeInboxCommand::class)
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command(ExpireIdentitySessionsCommand::class)
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command(ExpireIdentityChallengesCommand::class)
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command(PurgeTransientSecretsCommand::class)
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command(MonitorServiceTasksCommand::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping(10);

Schedule::command(ReviewVerificationExpiriesCommand::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping(10);

Schedule::command(SendVerificationRemindersCommand::class)
    ->dailyAt('08:00')
    ->timezone('Africa/Kinshasa')
    ->withoutOverlapping(30);
