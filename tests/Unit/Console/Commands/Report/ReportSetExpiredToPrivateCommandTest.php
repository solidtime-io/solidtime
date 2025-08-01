<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Report;

use App\Console\Commands\Report\ReportSetExpiredToPrivateCommand;
use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(ReportSetExpiredToPrivateCommand::class)]
class ReportSetExpiredToPrivateCommandTest extends TestCaseWithDatabase
{
    public function test_command_sets_expired_reports_to_private(): void
    {
        // Arrange
        $reportPrivateExpired = Report::factory()->private()->create([
            'public_until' => now()->subDay(),
        ]);
        $reportPublicExpired = Report::factory()->public()->create([
            'public_until' => now()->subDay(),
        ]);
        $reportPrivateNoExpiration = Report::factory()->private()->create([
            'public_until' => null,
        ]);
        $reportPublicNoExpiration = Report::factory()->public()->create([
            'public_until' => null,
        ]);
        $reportPrivateNotExpired = Report::factory()->private()->create([
            'public_until' => now()->addDay(),
        ]);
        $reportPublicNotExpired = Report::factory()->public()->create([
            'public_until' => now()->addDay(),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('report:set-expired-to-private');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Makes public reports private if the public_until date has passed...', $output);
        $this->assertStringContainsString('Make report "'.$reportPrivateExpired->name.'" ('.$reportPrivateExpired->getKey().') private, expired: '.$reportPrivateExpired->public_until->toIso8601ZuluString().' ('.$reportPrivateExpired->public_until->diffForHumans().')', $output);
        $this->assertStringContainsString('Make report "'.$reportPublicExpired->name.'" ('.$reportPublicExpired->getKey().') private, expired: '.$reportPublicExpired->public_until->toIso8601ZuluString().' ('.$reportPublicExpired->public_until->diffForHumans().')', $output);
        $this->assertStringContainsString('Finished setting 2 expired reports to private...', $output);
        $reportPrivateExpired->refresh();
        $reportPublicExpired->refresh();
        $reportPrivateNoExpiration->refresh();
        $reportPublicNoExpiration->refresh();
        $reportPrivateNotExpired->refresh();
        $reportPublicNotExpired->refresh();
        $this->assertFalse($reportPrivateExpired->is_public);
        $this->assertNull($reportPrivateExpired->share_secret);
        $this->assertFalse($reportPublicExpired->is_public);
        $this->assertNull($reportPublicExpired->share_secret);
        $this->assertFalse($reportPrivateNoExpiration->is_public);
        $this->assertNull($reportPrivateNoExpiration->share_secret);
        $this->assertTrue($reportPublicNoExpiration->is_public);
        $this->assertNotNull($reportPublicNoExpiration->share_secret);
        $this->assertFalse($reportPrivateNotExpired->is_public);
        $this->assertNull($reportPrivateNotExpired->share_secret);
        $this->assertTrue($reportPublicNotExpired->is_public);
        $this->assertNotNull($reportPublicNotExpired->share_secret);
    }

    public function test_command_sets_expired_reports_to_private_in_dry_run_mode(): void
    {
        // Arrange
        $reportPrivateExpired = Report::factory()->private()->create([
            'public_until' => now()->subDay(),
        ]);
        $reportPublicExpired = Report::factory()->public()->create([
            'public_until' => now()->subDay(),
        ]);
        $reportPrivateNoExpiration = Report::factory()->private()->create([
            'public_until' => null,
        ]);
        $reportPublicNoExpiration = Report::factory()->public()->create([
            'public_until' => null,
        ]);
        $reportPrivateNotExpired = Report::factory()->private()->create([
            'public_until' => now()->addDay(),
        ]);
        $reportPublicNotExpired = Report::factory()->public()->create([
            'public_until' => now()->addDay(),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('report:set-expired-to-private', ['--dry-run' => true]);

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Makes public reports private if the public_until date has passed...', $output);
        $this->assertStringContainsString('Running in dry-run mode. Nothing will be saved to the database.', $output);
        $this->assertStringContainsString('Make report "'.$reportPrivateExpired->name.'" ('.$reportPrivateExpired->getKey().') private, expired: '.$reportPrivateExpired->public_until->toIso8601ZuluString().' ('.$reportPrivateExpired->public_until->diffForHumans().')', $output);
        $this->assertStringContainsString('Make report "'.$reportPublicExpired->name.'" ('.$reportPublicExpired->getKey().') private, expired: '.$reportPublicExpired->public_until->toIso8601ZuluString().' ('.$reportPublicExpired->public_until->diffForHumans().')', $output);
        $this->assertStringContainsString('Finished setting 2 expired reports to private...', $output);
        $reportPrivateExpired->refresh();
        $reportPublicExpired->refresh();
        $reportPrivateNoExpiration->refresh();
        $reportPublicNoExpiration->refresh();
        $reportPrivateNotExpired->refresh();
        $reportPublicNotExpired->refresh();
        $this->assertFalse($reportPrivateExpired->is_public);
        $this->assertNull($reportPrivateExpired->share_secret);
        $this->assertTrue($reportPublicExpired->is_public);
        $this->assertNotNull($reportPublicExpired->share_secret);
        $this->assertFalse($reportPrivateNoExpiration->is_public);
        $this->assertNull($reportPrivateNoExpiration->share_secret);
        $this->assertTrue($reportPublicNoExpiration->is_public);
        $this->assertNotNull($reportPublicNoExpiration->share_secret);
        $this->assertFalse($reportPrivateNotExpired->is_public);
        $this->assertNull($reportPrivateNotExpired->share_secret);
        $this->assertTrue($reportPublicNotExpired->is_public);
        $this->assertNotNull($reportPublicNotExpired->share_secret);
    }
}
