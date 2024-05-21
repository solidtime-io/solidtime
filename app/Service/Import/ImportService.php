<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Models\Organization;
use App\Service\Import\Importers\ImporterContract;
use App\Service\Import\Importers\ImporterProvider;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\ReportDto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportService
{
    /**
     * @throws ImportException
     */
    public function import(Organization $organization, string $importerType, string $data): ReportDto
    {
        /** @var ImporterContract $importer */
        $importer = app(ImporterProvider::class)->getImporter($importerType);
        $importer->init($organization);
        Storage::disk('s3')->put('import/'.Carbon::now()->toDateString().'-'.$organization->getKey().'-'.Str::uuid(), $data);

        DB::transaction(function () use (&$importer, &$data) {
            $importer->importData($data);
        });

        return $importer->getReport();
    }
}
