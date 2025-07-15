<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\SelfHost;

use App\Console\Commands\SelfHost\SelfHostTelemetryCommand;
use App\Service\ApiService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(SelfHostTelemetryCommand::class)]
#[CoversClass(ApiService::class)]
class SelfHostTelemetryCommandTest extends TestCase
{
    public function test_telemetry_sends_data_to_telemetry_endpoint_of_solidtime_cloud(): void
    {
        // Arrange
        Http::fake([
            'https://app.solidtime.io/api/v1/ping/telemetry' => Http::response(['success' => true], 200),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:telemetry');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertSame('', $output);
    }

    public function test_telemetry_sends_fails_gracefully_if_response_has_error_status_code(): void
    {
        // Arrange
        Http::fake([
            'https://app.solidtime.io/api/v1/ping/telemetry' => Http::response(null, 500),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:telemetry');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Failed to send telemetry data, check the logs for more information.', $output);
    }

    public function test_telemetry_sends_fails_gracefully_if_timeout_happens(): void
    {
        // Arrange
        Http::fake([
            'https://app.solidtime.io/api/v1/ping/telemetry' => function (): void {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:telemetry');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Failed to send telemetry data, check the logs for more information.', $output);
    }
}
