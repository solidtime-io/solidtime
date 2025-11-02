<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('time-entry:send-still-running-mails')
            ->when(fn (): bool => config('scheduling.tasks.time_entry_send_still_running_mails'))
            ->everyTenMinutes();

        $schedule->command('auth:send-mails-expiring-api-tokens')
            ->when(fn (): bool => config('scheduling.tasks.auth_send_mails_expiring_api_tokens'))
            ->everyTenMinutes();

        if (config('scheduling.tasks.self_hosting_check_for_update') || config('scheduling.tasks.self_hosting_telemetry')) {
            // Convert string to a stable integer for seeding
            /** @var int $seed Take the first 8 hex chars → 32-bit int */
            $seed = hexdec(substr(hash('md5', config('app.key')), 0, 8));
            $seed = abs($seed); // Ensure it's positive
            mt_srand($seed);
            $firstHour = mt_rand(0, 23);
            $secondHour = ($firstHour + 12) % 24;
            $minuteOffset = mt_rand(0, 59);
            mt_srand(null); // Reset the random number generator

            if (config('scheduling.tasks.self_hosting_check_for_update')) {
                $schedule->command('self-host:check-for-update')
                    ->twiceDailyAt($firstHour, $secondHour, $minuteOffset);
            }

            if (config('scheduling.tasks.self_hosting_telemetry')) {
                $schedule->command('self-host:telemetry')
                    ->twiceDailyAt($firstHour, $secondHour, $minuteOffset);
            }
        }

        $schedule->command('self-host:database-consistency')
            ->when(fn (): bool => config('scheduling.tasks.self_hosting_database_consistency'))
            ->everySixHours();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
