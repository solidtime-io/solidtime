<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importer;

use App\Models\Organization;
use App\Service\Import\Importers\ClockifyProjectsImporter;

class ClockifyProjectsImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $importer = new ClockifyProjectsImporter();
        $importer->init($organization);
        $data = file_get_contents(storage_path('tests/clockify_projects_import_test_1.csv'));

        // Act
        $importer->importData($data, []);

        // Assert
        $this->checkTestScenarioProjectsOnlyAfterImport();
    }

    public function test_import_of_test_file_twice_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $importer = new ClockifyProjectsImporter();
        $importer->init($organization);
        $data = file_get_contents(storage_path('tests/clockify_projects_import_test_1.csv'));
        $importer->importData($data, []);
        $importer = new ClockifyProjectsImporter();
        $importer->init($organization);

        // Act
        $importer->importData($data, []);

        // Assert
        $this->checkTestScenarioProjectsOnlyAfterImport();
    }
}
