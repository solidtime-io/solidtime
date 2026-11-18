<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Export;

use App\Console\Commands\Export\ExportPruneCommand;
use App\Enums\ExportStatus;
use App\Models\Export;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(ExportPruneCommand::class)]
class ExportPruneCommandTest extends TestCaseWithDatabase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'export.job_timeout' => 3600,
            'export.file_retention_days' => 7,
        ]);
        Storage::fake(config('filesystems.private'));
    }

    public function test_command_marks_stale_active_exports_as_failed(): void
    {
        // Arrange
        $stalePending = Export::factory()->create([
            'created_at' => now()->subHours(3),
        ]);
        $recentPending = Export::factory()->create([
            'created_at' => now()->subMinutes(90),
        ]);
        $staleRunning = Export::factory()->running()->create([
            'started_at' => now()->subMinutes(70),
        ]);
        $recentRunning = Export::factory()->running()->create([
            'started_at' => now()->subMinutes(50),
        ]);
        $oldCompleted = Export::factory()->completed()->create([
            'created_at' => now()->subHours(3),
            'started_at' => now()->subHours(3),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('export:prune');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Marked 2 stale exports as failed and deleted 0 export files.', Artisan::output());
        $this->assertSame(ExportStatus::Failed, $stalePending->refresh()->status);
        $this->assertSame('Export could not be finished', $stalePending->error_message);
        $this->assertSame(ExportStatus::Pending, $recentPending->refresh()->status);
        $this->assertSame(ExportStatus::Failed, $staleRunning->refresh()->status);
        $this->assertSame(ExportStatus::Running, $recentRunning->refresh()->status);
        $this->assertSame(ExportStatus::Completed, $oldCompleted->refresh()->status);
    }

    public function test_command_deletes_files_of_exports_after_retention_period(): void
    {
        // Arrange
        $disk = Storage::disk(config('filesystems.private'));
        $disk->put('export/old', 'old');
        $disk->put('export/new', 'new');
        $oldExport = Export::factory()->completed()->create([
            'file_path' => 'export/old',
            'finished_at' => now()->subDays(8),
        ]);
        $newExport = Export::factory()->failed()->create([
            'file_path' => 'export/new',
            'finished_at' => now()->subDays(6),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('export:prune');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Marked 0 stale exports as failed and deleted 1 export files.', Artisan::output());
        $this->assertNull($oldExport->refresh()->file_path);
        $this->assertFalse($disk->exists('export/old'));
        $this->assertSame('export/new', $newExport->refresh()->file_path);
        $this->assertTrue($disk->exists('export/new'));
    }

    public function test_command_does_not_change_anything_in_dry_run_mode(): void
    {
        // Arrange
        $disk = Storage::disk(config('filesystems.private'));
        $disk->put('export/old', 'old');
        $staleExport = Export::factory()->running()->create([
            'started_at' => now()->subHours(3),
        ]);
        $oldExport = Export::factory()->completed()->create([
            'file_path' => 'export/old',
            'finished_at' => now()->subDays(8),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('export:prune', ['--dry-run' => true]);

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Marked 1 stale exports as failed and deleted 1 export files.', Artisan::output());
        $this->assertSame(ExportStatus::Running, $staleExport->refresh()->status);
        $this->assertSame('export/old', $oldExport->refresh()->file_path);
        $this->assertTrue($disk->exists('export/old'));
    }
}
