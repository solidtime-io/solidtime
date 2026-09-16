<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Models\Organization;
use App\Service\Import\Importers\ImporterContract;
use App\Service\Import\Importers\ImporterProvider;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\ReportDto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

        $lock = Cache::lock('import:'.$organization->getKey(), config('octane.max_execution_time', 60) + 1);

        if ($lock->get()) {
            try {
                DB::transaction(function () use (&$importer, &$data, &$timezone): void {
                    $importer->importData($data, $timezone);
                });
            } finally {
                $lock->release();
            }
        } else {
            throw new ImportException('Import is already in progress');
        }

        return $importer->getReport();
    }
}
