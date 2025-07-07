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

        $schedule->command('self-host:check-for-update')
            ->when(fn (): bool => config('scheduling.tasks.self_hosting_check_for_update'))
            ->twiceDaily();

        $schedule->command('self-host:telemetry')
            ->when(fn (): bool => config('scheduling.tasks.self_hosting_telemetry'))
            ->twiceDaily();

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
