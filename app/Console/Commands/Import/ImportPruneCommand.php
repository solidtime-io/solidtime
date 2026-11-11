<?php

declare(strict_types=1);

namespace App\Console\Commands\Import;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Service\Import\ImportService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ImportPruneCommand extends Command
{
    /**
     * Time in seconds a running import can exceed the job timeout before it is considered stale.
     * The queue worker kills the job after the timeout, so this only needs to cover small delays.
     * Note: The job itself can not mark a timed out import as failed, since the timeout handler runs while the
     * import transaction is still open and the change is rolled back when the worker is killed.
     */
    private const int RUNNING_STALE_BUFFER_SECONDS = 300;

    /**
     * Time in seconds a pending import can exceed the job timeout before it is considered stale,
     * this covers the time the job waits in the queue.
     */
    private const int PENDING_STALE_BUFFER_SECONDS = 3600;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:prune '.
        ' { --dry-run : Do not actually save or delete anything, just output what would happen }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marks stale imports as failed and deletes import files after the retention period.';

    /**
     * Execute the console command.
     */
    public function handle(ImportService $importService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->comment('Running in dry-run mode. Nothing will be saved or deleted.');
        }

        $jobTimeout = (int) config('import.job_timeout');
        $runningStaleBefore = Carbon::now()->subSeconds($jobTimeout + self::RUNNING_STALE_BUFFER_SECONDS);
        $pendingStaleBefore = Carbon::now()->subSeconds($jobTimeout + self::PENDING_STALE_BUFFER_SECONDS);
        $staleImports = 0;
        Import::query()
            ->where(function (Builder $query) use ($runningStaleBefore, $pendingStaleBefore): void {
                $query->where(function (Builder $query) use ($runningStaleBefore): void {
                    $query->where('status', ImportStatus::Running)
                        ->where('started_at', '<', $runningStaleBefore);
                })->orWhere(function (Builder $query) use ($pendingStaleBefore): void {
                    $query->where('status', ImportStatus::Pending)
                        ->where('created_at', '<', $pendingStaleBefore);
                });
            })
            ->orderBy('created_at', 'asc')
            ->chunkById(500, function (Collection $imports) use ($dryRun, $importService, &$staleImports): void {
                /** @var Collection<int, Import> $imports */
                foreach ($imports as $import) {
                    $this->info('Mark stale import '.$import->getKey().' ('.$import->status->value.') as failed');
                    $staleImports++;
                    if (! $dryRun) {
                        $importService->markAsFailed($import, 'Import could not be finished');
                    }
                }
            });

        $retentionBefore = Carbon::now()->subDays((int) config('import.file_retention_days'));
        $prunedFiles = 0;
        Import::query()
            ->whereNotNull('file_path')
            ->whereNotNull('finished_at')
            ->where('finished_at', '<', $retentionBefore)
            ->orderBy('created_at', 'asc')
            ->chunkById(500, function (Collection $imports) use ($dryRun, &$prunedFiles): void {
                /** @var Collection<int, Import> $imports */
                foreach ($imports as $import) {
                    $this->info('Delete file of import '.$import->getKey().' ('.$import->file_path.')');
                    $prunedFiles++;
                    if (! $dryRun) {
                        Storage::disk(config('filesystems.private'))->delete((string) $import->file_path);
                        $import->file_path = null;
                        $import->save();
                    }
                }
            });

        $this->comment('Marked '.$staleImports.' stale imports as failed and deleted '.$prunedFiles.' import files.');

        return self::SUCCESS;
    }
}
