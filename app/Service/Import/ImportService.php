<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Models\Organization;
use App\Service\Import\Importers\ImporterContract;
use App\Service\Import\Importers\ImporterProvider;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\ReportDto;
use Illuminate\Support\Facades\DB;

class ImportService
{
    /**
     * @throws ImportException
     */
    public function import(Organization $organization, string $importerType, string $data, array $options): ReportDto
    {
        /** @var ImporterContract $importer */
        $importer = app(ImporterProvider::class)->getImporter($importerType);
        $importer->init($organization);
        DB::transaction(function () use (&$importer, &$data, &$options, &$organization) {
            $importer->importData($data, $options);
        });

        return $importer->getReport();
    }
}
