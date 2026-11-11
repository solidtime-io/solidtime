<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\ImportStatus;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Service\Import\ImportService;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\TimeoutExceededException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCaseWithDatabase;

#[CoversClass(ProcessImport::class)]
class ProcessImportTest extends TestCaseWithDatabase
{
    public function test_job_processes_import_via_import_service(): void
    {
        // Arrange
        $import = Import::factory()->create();
        $this->mock(ImportService::class, function (MockInterface $mock) use ($import): void {
            $mock->shouldReceive('process')
                ->withArgs(fn (Import $processedImport): bool => $processedImport->is($import))
                ->once();
        });

        // Act
        ProcessImport::dispatchSync($import);
    }

    public function test_job_uses_timeout_and_queue_from_config(): void
    {
        // Arrange
        config([
            'import.job_timeout' => 123,
            'import.queue' => 'imports',
        ]);
        $import = Import::factory()->create();

        // Act
        $job = new ProcessImport($import);

        // Assert
        $this->assertSame(123, $job->timeout);
        $this->assertSame(1, $job->tries);
        $this->assertSame('imports', $job->queue);
    }

    public function test_failed_marks_active_import_as_failed(): void
    {
        // Arrange
        $import = Import::factory()->running()->create();
        $job = new ProcessImport($import);

        // Act
        $job->failed(new RuntimeException('Timeout'));

        // Assert
        $import->refresh();
        $this->assertSame(ImportStatus::Failed, $import->status);
        $this->assertSame('Import could not be finished', $import->error_message);
    }

    public function test_failed_does_not_change_finished_import(): void
    {
        // Arrange
        $import = Import::factory()->completed()->create();
        $job = new ProcessImport($import);

        // Act
        $job->failed(new RuntimeException('Timeout'));

        // Assert
        $import->refresh();
        $this->assertSame(ImportStatus::Completed, $import->status);
        $this->assertNull($import->error_message);
    }

    public function test_failed_does_not_mark_import_as_failed_if_job_was_handed_out_again_while_still_running(): void
    {
        // Arrange
        $import = Import::factory()->running()->create();
        $job = new ProcessImport($import);

        // Act
        $job->failed(new MaxAttemptsExceededException('App\\Jobs\\ProcessImport has been attempted too many times.'));

        // Assert
        $import->refresh();
        $this->assertSame(ImportStatus::Running, $import->status);
        $this->assertNull($import->error_message);
    }

    public function test_failed_does_not_mark_import_as_failed_on_timeout(): void
    {
        // Arrange
        $import = Import::factory()->running()->create();
        $job = new ProcessImport($import);

        // Act
        $job->failed(new TimeoutExceededException('App\\Jobs\\ProcessImport has timed out.'));

        // Assert
        $import->refresh();
        $this->assertSame(ImportStatus::Running, $import->status);
        $this->assertNull($import->error_message);
    }
}
