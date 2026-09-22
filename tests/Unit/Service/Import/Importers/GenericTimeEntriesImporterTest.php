<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Models\Organization;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\GenericTimeEntriesImporter;
use App\Service\Import\Importers\ImportException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GenericTimeEntriesImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
class GenericTimeEntriesImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new GenericTimeEntriesImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('generic_time_entries_import_test_1.csv');

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $testScenario = $this->checkTestScenarioAfterImportExcludingTimeEntries();
        $this->checkTimeEntries($testScenario);
        $this->assertSame(2, $report->timeEntriesCreated);
        $this->assertSame(2, $report->tagsCreated);
        $this->assertSame(1, $report->tasksCreated);
        $this->assertSame(1, $report->usersCreated);
        $this->assertSame(2, $report->projectsCreated);
        $this->assertSame(1, $report->clientsCreated);
    }

    public function test_import_of_test_file_twice_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new GenericTimeEntriesImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('generic_time_entries_import_test_1.csv');
        $importer->importData($data, $timezone);
        $importer = new GenericTimeEntriesImporter;
        $importer->init($organization);

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $testScenario = $this->checkTestScenarioAfterImportExcludingTimeEntries();
        $this->checkTimeEntries($testScenario, true);
        $this->assertSame(2, $report->timeEntriesCreated);
        $this->assertSame(0, $report->tagsCreated);
        $this->assertSame(0, $report->tasksCreated);
        $this->assertSame(0, $report->usersCreated);
        $this->assertSame(0, $report->projectsCreated);
        $this->assertSame(0, $report->clientsCreated);
    }

    public function test_import_fails_if_task_name_is_too_long(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new GenericTimeEntriesImporter;
        $importer->init($organization);
        $taskName = str_repeat('a', 501);
        $data = "description,billable,client,project,tags,start,end,task,user_name,user_email\n".
            '"Working hard","true","Big Company","Project for Big Company","","2024-03-04T09:23:00Z","2024-03-04T10:23:01Z","'.$taskName.'","Peter Tester","peter.test@email.test"';

        // Act
        try {
            $importer->importData($data, $timezone);
        } catch (ImportException $e) {
            // Assert
            $this->assertSame('Task name ("'.$taskName.'") is too long, maximum length is 500 characters', $e->getMessage());

            return;
        }
        $this->fail();
    }
}
