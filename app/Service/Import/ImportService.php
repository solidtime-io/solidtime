<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Models\Organization;
use App\Service\Import\Importers\ImporterContract;
use App\Service\Import\Importers\ImporterProvider;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\ReportDto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportService
{
    /**
     * @throws ImportException
     */
    public function import(Organization $organization, string $importerType, string $data, string $timezone): ReportDto
    {
        /** @var ImporterContract $importer */
        $importer = app(ImporterProvider::class)->getImporter($importerType);
        $importer->init($organization);
        Storage::disk(config('filesystems.default'))
            ->put('import/'.Carbon::now()->toDateString().'-'.$organization->getKey().'-'.Str::uuid(), $data);

        $lock = Cache::lock('import:'.$organization->getKey(), config('octane.max_execution_time', 60) + 1);

        if ($lock->get()) {
            DB::transaction(function () use (&$importer, &$data, &$timezone): void {
                $importer->importData($data, $timezone);
            });
            $lock->release();
        } else {
            throw new ImportException('Import is already in progress');
        }

        return $importer->getReport();
    }
}
