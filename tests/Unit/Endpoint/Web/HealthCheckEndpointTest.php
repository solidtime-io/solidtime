<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Http\Controllers\Web\HealthCheckController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(HealthCheckController::class)]
#[UsesClass(HealthCheckController::class)]
class HealthCheckEndpointTest extends EndpointTestAbstract
{
    public function test_up_endpoint_returns_ok(): void
    {
        // Act
        $response = $this->get('health-check/up');

        // Assert
        $response->assertSuccessful();
        $response->assertExactJson([
            'success' => true,
        ]);
    }

    public function test_debug_endpoint_returns_ok(): void
    {
        // Act
        $response = $this->get('health-check/debug');

        // Assert
        $response->assertSuccessful();
        $response->assertJsonStructure([
            'ip_address',
            'hostname',
            'timestamp',
            'date_time_utc',
            'date_time_app',
            'timezone',
            'secure',
            'is_trusted_proxy',
        ]);
    }
}
