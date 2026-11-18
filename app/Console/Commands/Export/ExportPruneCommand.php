<?php

declare(strict_types=1);

namespace App\Console\Commands\Export;

use App\Models\Export;
use App\Service\Export\ExportService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ExportPruneCommand extends Command
{
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

        $staleExports = 0;
        Export::query()
            ->stale()
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
