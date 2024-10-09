<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\SelfHost;

use App\Console\Commands\SelfHost\SelfHostCheckForUpdateCommand;
use App\Service\ApiService;
use Cache;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

#[CoversClass(SelfHostCheckForUpdateCommand::class)]
#[CoversClass(ApiService::class)]
#[UsesClass(SelfHostCheckForUpdateCommand::class)]
class SelfHostCheckForUpdateCommandTest extends TestCase
{
    public function test_checks_for_update_and_saves_version_in_cache(): void
    {
        // Arrange
        Http::fake([
            'https://app.solidtime.io/api/v1/ping/version' => Http::response(['version' => '1.2.3'], 200),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:check-for-update');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertSame('1.2.3', Cache::get('latest_version'));
    }

    public function test_checks_for_update_fails_gracefully_if_response_has_error_status_code(): void
    {
        // Arrange
        Http::fake([
            'https://app.solidtime.io/api/v1/ping/version' => Http::response(null, 500),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:check-for-update');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Failed to check for update, check the logs for more information.', $output);
    }

    public function test_checks_for_update_fails_gracefully_if_timeout_happens(): void
    {
        // Arrange
        Http::fake([
            'https://app.solidtime.io/api/v1/ping/version' => function (): void {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:check-for-update');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Failed to check for update, check the logs for more information.', $output);
    }
}
