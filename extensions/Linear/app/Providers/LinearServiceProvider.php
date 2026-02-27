<?php

declare(strict_types=1);

namespace Extensions\Linear\Providers;

use Extensions\Linear\Console\SyncLinearCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class LinearServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncLinearCommand::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('linear:sync')->everyFifteenMinutes();
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/linear.php', 'linear');
    }
}
