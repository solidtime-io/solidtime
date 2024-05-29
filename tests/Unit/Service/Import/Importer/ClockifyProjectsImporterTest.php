<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importer;

use App\Models\Organization;
use App\Service\Import\Importers\ClockifyProjectsImporter;
use Illuminate\Support\Facades\Storage;

class ClockifyProjectsImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyProjectsImporter();
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
        $importer = new ClockifyProjectsImporter();
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('clockify_projects_import_test_1.csv');
        $importer->importData($data, $timezone);
        $importer = new ClockifyProjectsImporter();
        $importer->init($organization);

        // Act
        $importer->importData($data, $timezone);

        // Assert
        $this->checkTestScenarioProjectsOnlyAfterImport();
    }
}
