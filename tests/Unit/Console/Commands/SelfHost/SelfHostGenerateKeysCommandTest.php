<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\SelfHost;

use App\Console\Commands\SelfHost\SelfHostGenerateKeysCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

#[CoversClass(SelfHostGenerateKeysCommand::class)]
#[UsesClass(SelfHostGenerateKeysCommand::class)]
class SelfHostGenerateKeysCommandTest extends TestCase
{
    public function test_generates_app_key_and_passport_keys_per_default_in_env_format(): void
    {
        // Arrange

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:generate-keys');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('APP_KEY="base64:', $output);
        $this->assertStringContainsString('PASSPORT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----', $output);
        $this->assertStringContainsString('PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----', $output);
    }

    public function test_generates_app_key_and_passport_keys_in_yaml_format_if_requested(): void
    {
        // Arrange

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:generate-keys --format=yaml');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('APP_KEY: "base64:', $output);
        $this->assertStringContainsString("PASSPORT_PRIVATE_KEY: |\n  -----BEGIN PRIVATE KEY-----", $output);
        $this->assertStringContainsString("PASSPORT_PUBLIC_KEY: |\n  -----BEGIN PUBLIC KEY-----", $output);
    }

    public function test_generates_app_fail_if_attribute_format_is_invalid(): void
    {
        // Arrange

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:generate-keys --format=invalid');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Invalid format\n", $output);
    }
}
