<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Web\HealthCheckController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            if (! $this->app->isProduction()) {
                return Limit::none();
            }

            return $request->user()
                ? Limit::perMinute(200)->by($request->user()->id)
                : Limit::perMinute(60)->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('health-check')
                ->group(function () {
                    Route::get('health-check/up', [HealthCheckController::class, 'up']);
                    Route::get('health-check/debug', [HealthCheckController::class, 'debug']);
                });

            Route::middleware('api')
                ->prefix('api')
                ->name('api.')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
