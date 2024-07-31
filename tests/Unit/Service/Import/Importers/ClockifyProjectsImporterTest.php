<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Models\Organization;
use App\Service\Import\Importers\ClockifyProjectsImporter;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\ImportException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ClockifyProjectsImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
#[UsesClass(ClockifyProjectsImporter::class)]
class ClockifyProjectsImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyProjectsImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('clockify_projects_import_test_1.csv');

        // Act
        $importer->importData($data, $timezone);

        // Assert
        $this->checkTestScenarioProjectsOnlyAfterImport();
    }

    public function test_import_of_test_file_twice_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyProjectsImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('clockify_projects_import_test_1.csv');
        $importer->importData($data, $timezone);
        $importer = new ClockifyProjectsImporter;
        $importer->init($organization);

        // Act
        $importer->importData($data, $timezone);

        // Assert
        $this->checkTestScenarioProjectsOnlyAfterImport();
    }
}
