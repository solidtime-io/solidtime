<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Export;
use App\Service\Export\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\TimeoutExceededException;
use Throwable;

class ProcessExport implements ShouldDispatchAfterCommit, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Exports are not retried, the user can start a new export.
     */
    public int $tries = 1;

    public int $timeout;

    public Export $export;

    /**
     * Create a new job instance.
     */
    public function __construct(Export $export)
    {
        $this->export = $export;
        $this->timeout = (int) config('export.job_timeout');
        $this->onQueue(config('export.queue'));
    }

    /**
     * Execute the job.
     */
    public function handle(ExportService $exportService): void
    {
        $exportService->process($this->export);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        // A MaxAttemptsExceededException (that is not a timeout) is thrown if the export runs longer than the
        // "retry_after" of the queue connection and the queue hands out the job a second time, while the first
        // attempt is still running. The export must not be marked as failed in this case.
        // The export:prune command marks the export as failed if it is really stale.
        // Note: Unlike imports, exports do not run in a database transaction, so a timed out export can be marked
        // as failed here.
        if ($exception instanceof MaxAttemptsExceededException && ! $exception instanceof TimeoutExceededException) {
            return;
        }

        $this->export->refresh();
        if ($this->export->status->isActive()) {
            app(ExportService::class)->markAsFailed($this->export, 'Export could not be finished');
        }
    }
}
