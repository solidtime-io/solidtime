<?php

declare(strict_types=1);

namespace Extensions\Linear\Providers;

use Illuminate\Support\ServiceProvider;

class LinearServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function register(): void
    {
        //
    }
}
