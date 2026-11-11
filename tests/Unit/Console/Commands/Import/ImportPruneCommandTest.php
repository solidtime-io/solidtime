<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Import;

use App\Console\Commands\Import\ImportPruneCommand;
use App\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(ImportPruneCommand::class)]
class ImportPruneCommandTest extends TestCaseWithDatabase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'import.job_timeout' => 3600,
            'import.file_retention_days' => 30,
        ]);
        Storage::fake(config('filesystems.private'));
    }

    public function test_command_marks_stale_active_imports_as_failed(): void
    {
        // Arrange
        $stalePending = Import::factory()->create([
            'created_at' => now()->subHours(3),
        ]);
        $recentPending = Import::factory()->create([
            'created_at' => now()->subMinutes(90),
        ]);
        $staleRunning = Import::factory()->running()->create([
            'started_at' => now()->subMinutes(70),
        ]);
        $recentRunning = Import::factory()->running()->create([
            'started_at' => now()->subMinutes(50),
        ]);
        $oldCompleted = Import::factory()->completed()->create([
            'created_at' => now()->subHours(3),
            'started_at' => now()->subHours(3),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('import:prune');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Marked 2 stale imports as failed and deleted 0 import files.', Artisan::output());
        $this->assertSame(ImportStatus::Failed, $stalePending->refresh()->status);
        $this->assertSame('Import could not be finished', $stalePending->error_message);
        $this->assertSame(ImportStatus::Pending, $recentPending->refresh()->status);
        $this->assertSame(ImportStatus::Failed, $staleRunning->refresh()->status);
        $this->assertSame(ImportStatus::Running, $recentRunning->refresh()->status);
        $this->assertSame(ImportStatus::Completed, $oldCompleted->refresh()->status);
    }

    public function test_command_deletes_files_of_imports_after_retention_period(): void
    {
        // Arrange
        $disk = Storage::disk(config('filesystems.private'));
        $disk->put('import/old', 'old');
        $disk->put('import/new', 'new');
        $oldImport = Import::factory()->completed()->create([
            'file_path' => 'import/old',
            'finished_at' => now()->subDays(31),
        ]);
        $newImport = Import::factory()->failed()->create([
            'file_path' => 'import/new',
            'finished_at' => now()->subDays(29),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('import:prune');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Marked 0 stale imports as failed and deleted 1 import files.', Artisan::output());
        $this->assertNull($oldImport->refresh()->file_path);
        $this->assertFalse($disk->exists('import/old'));
        $this->assertSame('import/new', $newImport->refresh()->file_path);
        $this->assertTrue($disk->exists('import/new'));
    }

    public function test_command_does_not_change_anything_in_dry_run_mode(): void
    {
        // Arrange
        $disk = Storage::disk(config('filesystems.private'));
        $disk->put('import/old', 'old');
        $staleImport = Import::factory()->running()->create([
            'started_at' => now()->subHours(3),
        ]);
        $oldImport = Import::factory()->completed()->create([
            'file_path' => 'import/old',
            'finished_at' => now()->subDays(31),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('import:prune', ['--dry-run' => true]);

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Marked 1 stale imports as failed and deleted 1 import files.', Artisan::output());
        $this->assertSame(ImportStatus::Running, $staleImport->refresh()->status);
        $this->assertSame('import/old', $oldImport->refresh()->file_path);
        $this->assertTrue($disk->exists('import/old'));
    }
}
