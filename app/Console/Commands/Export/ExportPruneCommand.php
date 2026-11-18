<?php

declare(strict_types=1);

namespace App\Console\Commands\Export;

use App\Enums\ExportStatus;
use App\Models\Export;
use App\Service\Export\ExportService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ExportPruneCommand extends Command
{
    /**
     * Time in seconds a running export can exceed the job timeout before it is considered stale.
     * The queue worker kills the job after the timeout, so this only needs to cover small delays.
     */
    private const int RUNNING_STALE_BUFFER_SECONDS = 300;

    /**
     * Time in seconds a pending export can exceed the job timeout before it is considered stale,
     * this covers the time the job waits in the queue.
     */
    private const int PENDING_STALE_BUFFER_SECONDS = 3600;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:prune '.
        ' { --dry-run : Do not actually save or delete anything, just output what would happen }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marks stale exports as failed and deletes export files after the retention period.';

    /**
     * Execute the console command.
     */
    public function handle(ExportService $exportService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->comment('Running in dry-run mode. Nothing will be saved or deleted.');
        }

        $jobTimeout = (int) config('export.job_timeout');
        $runningStaleBefore = Carbon::now()->subSeconds($jobTimeout + self::RUNNING_STALE_BUFFER_SECONDS);
        $pendingStaleBefore = Carbon::now()->subSeconds($jobTimeout + self::PENDING_STALE_BUFFER_SECONDS);
        $staleExports = 0;
        Export::query()
            ->where(function (Builder $query) use ($runningStaleBefore, $pendingStaleBefore): void {
                $query->where(function (Builder $query) use ($runningStaleBefore): void {
                    $query->where('status', ExportStatus::Running)
                        ->where('started_at', '<', $runningStaleBefore);
                })->orWhere(function (Builder $query) use ($pendingStaleBefore): void {
                    $query->where('status', ExportStatus::Pending)
                        ->where('created_at', '<', $pendingStaleBefore);
                });
            })
            ->orderBy('created_at', 'asc')
            ->chunkById(500, function (Collection $exports) use ($dryRun, $exportService, &$staleExports): void {
                /** @var Collection<int, Export> $exports */
                foreach ($exports as $export) {
                    $this->info('Mark stale export '.$export->getKey().' ('.$export->status->value.') as failed');
                    $staleExports++;
                    if (! $dryRun) {
                        $exportService->markAsFailed($export, 'Export could not be finished');
                    }
                }
            });

        $retentionBefore = Carbon::now()->subDays((int) config('export.file_retention_days'));
        $prunedFiles = 0;
        Export::query()
            ->whereNotNull('file_path')
            ->whereNotNull('finished_at')
            ->where('finished_at', '<', $retentionBefore)
            ->orderBy('created_at', 'asc')
            ->chunkById(500, function (Collection $exports) use ($dryRun, &$prunedFiles): void {
                /** @var Collection<int, Export> $exports */
                foreach ($exports as $export) {
                    $this->info('Delete file of export '.$export->getKey().' ('.$export->file_path.')');
                    $prunedFiles++;
                    if (! $dryRun) {
                        Storage::disk(config('filesystems.private'))->delete((string) $export->file_path);
                        $export->file_path = null;
                        $export->save();
                    }
                }
            });

        $this->comment('Marked '.$staleExports.' stale exports as failed and deleted '.$prunedFiles.' export files.');

        return self::SUCCESS;
    }
}
