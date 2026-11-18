<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Enums\ImportSource;
use App\Enums\ImportStatus;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Models\Organization;
use App\Models\User;
use App\Service\ErrorDetailsFormatter;
use App\Service\Import\Importers\ImporterContract;
use App\Service\Import\Importers\ImporterProvider;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\ReportDto;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Throwable;

class ImportService
{
    /**
     * Stores the import file and queues the import.
     *
     * @param  bool  $ignoreLimits  Skip the import limits, only for imports started by a super admin (admin panel)
     *
     * @throws ImportException
     */
    public function createFileImport(Organization $organization, ?User $user, string $importerType, string $data, string $timezone, bool $ignoreLimits = false): Import
    {
        // Throws an InvalidArgumentException if the importer type is unknown
        app(ImporterProvider::class)->getImporter($importerType);

        // Stale imports would block new imports until the import:prune command runs
        Import::query()->whereBelongsTo($organization, 'organization')->stale()->get()
            ->each(fn (Import $staleImport) => $this->markAsFailed($staleImport, 'Import could not be finished'));

        if (Import::query()->whereBelongsTo($organization, 'organization')->active()->exists()) {
            throw new ImportException('Import is already in progress');
        }

        $import = new Import;
        $import->type = $importerType;
        $import->source = ImportSource::File;
        $import->status = ImportStatus::Pending;
        $import->timezone = $timezone;
        $import->ignore_limits = $ignoreLimits;
        $import->organization()->associate($organization);
        $import->user()->associate($user);
        $import->id = $import->newUniqueId();
        $import->file_path = 'import/'.$organization->getKey().'/'.$import->getKey();

        Storage::disk(config('filesystems.private'))->put($import->file_path, $data);

        try {
            $import->save();
        } catch (UniqueConstraintViolationException) {
            // Another import of the organization was created in the meantime
            Storage::disk(config('filesystems.private'))->delete($import->file_path);

            throw new ImportException('Import is already in progress');
        }

        ProcessImport::dispatch($import);

        return $import;
    }

    /**
     * Runs the import, the result (report or error message) is stored on the import.
     */
    public function process(Import $import): void
    {
        if ($import->status !== ImportStatus::Pending) {
            Log::warning('Skipping import that is not pending', [
                'import_id' => $import->getKey(),
                'status' => $import->status->value,
            ]);

            return;
        }

        $import->status = ImportStatus::Running;
        $import->started_at = Carbon::now();
        $import->save();

        try {
            /** @var ImporterContract $importer */
            $importer = app(ImporterProvider::class)->getImporter($import->type);
            $importer->init($import->organization);
            $data = $this->getImportData($import);
            DB::transaction(function () use (&$importer, &$data, $import): void {
                $importer->importData($data, $import->timezone);
                // Note: The limit is checked after the import, the exception rolls back all created records
                $this->checkLimits($import, $importer->getReport());
            });

            $import->status = ImportStatus::Completed;
            $import->report = $importer->getReport()->toArray();
        } catch (ImportException $exception) {
            // Import exceptions are not reported, they are caused by invalid import data or the importers already
            // reported the underlying unexpected error
            $import->status = ImportStatus::Failed;
            $import->error_message = $exception->getMessage();
            $import->error_details = ErrorDetailsFormatter::format($exception);
        } catch (Throwable $exception) {
            report($exception);
            $import->status = ImportStatus::Failed;
            $import->error_message = 'Unexpected error during import';
            $import->error_details = ErrorDetailsFormatter::format($exception);
        } finally {
            $import->credentials = null;
            $import->finished_at = Carbon::now();
            $import->save();
        }
    }

    /**
     * Marks an import as failed that could not be finished, for example because the job timed out or the worker crashed.
     */
    public function markAsFailed(Import $import, string $errorMessage): void
    {
        $import->status = ImportStatus::Failed;
        $import->error_message = $errorMessage;
        $import->credentials = null;
        $import->finished_at = Carbon::now();
        $import->save();
    }

    /**
     * @throws ImportException
     */
    private function checkLimits(Import $import, ReportDto $report): void
    {
        $maxRecords = config('import.max_records');
        if ($import->ignore_limits || $maxRecords === null) {
            return;
        }
        $totalCreated = $report->getTotalCreated();
        if ($totalCreated > $maxRecords) {
            throw new ImportException('The import would create '.$totalCreated.' records, but imports are limited to '.
                $maxRecords.' records. Bigger imports are possible, please contact the support.');
        }
    }

    /**
     * @throws ImportException
     */
    private function getImportData(Import $import): string
    {
        if ($import->source !== ImportSource::File) {
            throw new LogicException('Import source "'.$import->source->value.'" is not supported yet');
        }
        $disk = Storage::disk(config('filesystems.private'));
        if ($import->file_path === null || ! $disk->exists($import->file_path)) {
            throw new ImportException('Import file does not exist anymore');
        }
        $data = $disk->get($import->file_path);
        if ($data === null) {
            throw new ImportException('Import file does not exist anymore');
        }

        return $data;
    }
}
