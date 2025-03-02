<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Http\Controllers\Web\HealthCheckController;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(HealthCheckController::class)]
#[UsesClass(HealthCheckController::class)]
class HealthCheckEndpointTest extends EndpointTestAbstract
{
    public function test_up_endpoint_returns_ok(): void
    {
        // Arrange
        DB::enableQueryLog();
        DB::flushQueryLog();

        // Act
        $response = $this->get('health-check/up');
        $queryLog = DB::getQueryLog();

        // Assert
        $this->assertCount(0, $queryLog);
        $response->assertSuccessful();
        $response->assertExactJson([
            'success' => true,
        ]);
    }

    public function test_debug_endpoint_returns_ok(): void
    {
        // Arrange
        config(['app.debug' => false]);

        // Act
        $response = $this->get('health-check/debug');

        // Assert
        $response->assertSuccessful();
        $response->assertExactJsonStructure([
            'date_time_app',
            'date_time_utc',
            'hostname',
            'ip_address',
            'is_trusted_proxy',
            'path',
            'secure',
            'timestamp',
            'timezone',
            'url',
        ]);
        config(['app.debug' => true]);
    }

    public function test_debug_endpoint_returns_more_information_if_debug_mode_is_enabled(): void
    {
        // Arrange
        config(['app.debug' => true]);

        // Act
        $response = $this->get('health-check/debug');

        // Assert
        $response->assertSuccessful();
        $response->assertExactJsonStructure([
            'app_debug',
            'app_env',
            'app_force_https',
            'app_timezone',
            'app_url',
            'date_time_app',
            'date_time_utc',
            'headers',
            'hostname',
            'ip_address',
            'is_trusted_proxy',
            'path',
            'secure',
            'timestamp',
            'timezone',
            'session_secure',
            'trusted_proxies',
            'url',
        ]);
    }
}
