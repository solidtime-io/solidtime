<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Import;
use App\Service\Import\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessImport implements ShouldDispatchAfterCommit, ShouldQueue
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
     * Imports are not retried, since a failed import is rolled back and the user has to fix the import data.
     */
    public int $tries = 1;

    public int $timeout;

    public Import $import;

    /**
     * Create a new job instance.
     */
    public function __construct(Import $import)
    {
        $this->import = $import;
        $this->timeout = (int) config('import.job_timeout');
        $this->onQueue(config('import.queue'));
    }

    /**
     * Execute the job.
     */
    public function handle(ImportService $importService): void
    {
        $importService->process($this->import);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        // A MaxAttemptsExceededException is thrown in two cases where the import must not be marked as failed here:
        // - The import runs longer than the "retry_after" of the queue connection and the queue hands out the job
        //   a second time, while the first attempt is still running.
        // - The import timed out (TimeoutExceededException). The timeout handler runs while the import transaction
        //   is still open, so the change would be rolled back when the worker is killed.
        // In both cases the import:prune command marks the import as failed if it is really stale.
        if ($exception instanceof MaxAttemptsExceededException) {
            return;
        }

        $this->import->refresh();
        if ($this->import->status->isActive()) {
            app(ImportService::class)->markAsFailed($this->import, 'Import could not be finished');
        }
    }
}
