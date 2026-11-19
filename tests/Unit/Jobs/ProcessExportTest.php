<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\ExportStatus;
use App\Jobs\ProcessExport;
use App\Models\Export;
use App\Service\Export\ExportService;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\TimeoutExceededException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCaseWithDatabase;

#[CoversClass(ProcessExport::class)]
#[CoversClass(ExportStatus::class)]
class ProcessExportTest extends TestCaseWithDatabase
{
    public function test_job_processes_export_via_export_service(): void
    {
        // Arrange
        $export = Export::factory()->create();
        $this->mock(ExportService::class, function (MockInterface $mock) use ($export): void {
            $mock->shouldReceive('process')
                ->withArgs(fn (Export $processedExport): bool => $processedExport->is($export))
                ->once();
        });

        // Act
        ProcessExport::dispatchSync($export);
    }

    public function test_job_uses_timeout_and_queue_from_config(): void
    {
        // Arrange
        config([
            'export.job_timeout' => 123,
            'export.queue' => 'exports',
        ]);
        $export = Export::factory()->create();

        // Act
        $job = new ProcessExport($export);

        // Assert
        $this->assertSame(123, $job->timeout);
        $this->assertSame(1, $job->tries);
        $this->assertSame('exports', $job->queue);
    }

    public function test_failed_marks_active_export_as_failed(): void
    {
        // Arrange
        $export = Export::factory()->running()->create();
        $job = new ProcessExport($export);

        // Act
        $job->failed(new RuntimeException('Worker crashed'));

        // Assert
        $export->refresh();
        $this->assertSame(ExportStatus::Failed, $export->status);
        $this->assertSame('Export could not be finished', $export->error_message);
        $this->assertNotNull($export->finished_at);
    }

    public function test_failed_marks_export_as_failed_on_timeout(): void
    {
        // Arrange
        $export = Export::factory()->running()->create();
        $job = new ProcessExport($export);

        // Act
        $job->failed(new TimeoutExceededException('App\\Jobs\\ProcessExport has timed out.'));

        // Assert
        $export->refresh();
        $this->assertSame(ExportStatus::Failed, $export->status);
        $this->assertSame('Export could not be finished', $export->error_message);
    }

    public function test_failed_does_not_change_finished_export(): void
    {
        // Arrange
        $export = Export::factory()->completed()->create();
        $job = new ProcessExport($export);

        // Act
        $job->failed(new RuntimeException('Worker crashed'));

        // Assert
        $export->refresh();
        $this->assertSame(ExportStatus::Completed, $export->status);
        $this->assertNull($export->error_message);
    }

    public function test_failed_does_not_mark_export_as_failed_if_job_was_handed_out_again_while_still_running(): void
    {
        // Arrange
        $export = Export::factory()->running()->create();
        $job = new ProcessExport($export);

        // Act
        $job->failed(new MaxAttemptsExceededException('App\\Jobs\\ProcessExport has been attempted too many times.'));

        // Assert
        $export->refresh();
        $this->assertSame(ExportStatus::Running, $export->status);
        $this->assertNull($export->error_message);
    }
}
