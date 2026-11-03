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
     * Note: The "retry_after" of the queue connection needs to be higher than the timeout,
     * otherwise a long-running import is started a second time.
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
     * Handle a job failure, for example a timeout.
     */
    public function failed(?Throwable $exception): void
    {
        $this->import->refresh();
        if ($this->import->status->isActive()) {
            app(ImportService::class)->markAsFailed($this->import, 'Import could not be finished');
        }
    }
}
