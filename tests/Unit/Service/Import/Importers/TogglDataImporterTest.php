<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Models\Organization;
use App\Models\User;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\TogglDataImporter;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TogglDataImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
class TogglDataImporterTest extends ImporterTestAbstract
{
    public function test_import_throws_exception_if_data_is_not_zip(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new TogglDataImporter;
        $importer->init($organization);

        // Act
        try {
            $importer->importData('not a zip', $timezone);
        } catch (Exception $e) {
            $this->assertInstanceOf(ImportException::class, $e);
            $this->assertSame('Invalid ZIP, error code: 19', $e->getMessage());

            return;
        }
        $this->fail();
    }

    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $zipPath = $this->createTestZip('toggl_data_import_test_1');
        $timezone = 'Europe/Vienna';
        $organization = Organization::factory()->create();
        $importer = new TogglDataImporter;
        $importer->init($organization);
        $data = file_get_contents($zipPath);

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $this->checkTestScenarioAfterImportExcludingTimeEntries(true);
        $this->assertSame(0, $report->timeEntriesCreated);
        $this->assertSame(2, $report->tagsCreated);
        $this->assertSame(2, $report->tasksCreated);
        $this->assertSame(1, $report->usersCreated);
        $this->assertSame(3, $report->projectsCreated);
        $this->assertSame(2, $report->clientsCreated);
    }

    public function test_import_of_test_file_twice_succeeds(): void
    {
        // Arrange
        $zipPath = $this->createTestZip('toggl_data_import_test_1');
        $timezone = 'Europe/Vienna';
        $organization = Organization::factory()->create();
        $importer = new TogglDataImporter;
        $importer->init($organization);
        $data = file_get_contents($zipPath);
        $importer->importData($data, $timezone);
        $importer = new TogglDataImporter;
        $importer->init($organization);

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $this->checkTestScenarioAfterImportExcludingTimeEntries(true);
        $this->assertSame(0, $report->timeEntriesCreated);
        $this->assertSame(0, $report->tagsCreated);
        $this->assertSame(0, $report->tasksCreated);
        $this->assertSame(0, $report->usersCreated);
        $this->assertSame(0, $report->projectsCreated);
        $this->assertSame(0, $report->clientsCreated);
    }

    public function test_import_of_user_with_unknown_timezone_will_be_mapped_to_utc(): void
    {
        // Arrange
        $zipPath = $this->createTestZip('toggl_data_import_test_2');
        $timezone = 'Europe/Vienna';
        $organization = Organization::factory()->create();
        $importer = new TogglDataImporter;
        $importer->init($organization);
        $data = file_get_contents($zipPath);

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $this->assertSame(0, $report->timeEntriesCreated);
        $this->assertSame(2, $report->tagsCreated);
        $this->assertSame(2, $report->tasksCreated);
        $this->assertSame(1, $report->usersCreated);
        $this->assertSame(3, $report->projectsCreated);
        $this->assertSame(2, $report->clientsCreated);
        $user = User::query()->where('email', '=', 'peter.test@email.test')->first();
        $this->assertSame('UTC', $user->timezone);
        $this->assertTrue($user->is_placeholder);
    }
}
