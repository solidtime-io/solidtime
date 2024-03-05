<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importer;

use App\Models\Organization;
use App\Models\TimeEntry;
use App\Service\Import\Importers\TogglTimeEntriesImporter;

class TogglTimeEntriesImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $importer = new TogglTimeEntriesImporter();
        $importer->init($organization);
        $data = file_get_contents(storage_path('tests/toggl_import_test_1.csv'));

        // Act
        $importer->importData($data, []);

        // Assert
        $this->checkTestScenarioAfterImportExcludingTimeEntries();
    }

    public function test_import_of_test_file_twice_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $importer = new TogglTimeEntriesImporter();
        $importer->init($organization);
        $data = file_get_contents(storage_path('tests/toggl_import_test_1.csv'));
        $importer->importData($data, []);
        $importer = new TogglTimeEntriesImporter();
        $importer->init($organization);

        // Act
        $importer->importData($data, []);

        // Assert
        $testScenario = $this->checkTestScenarioAfterImportExcludingTimeEntries();
        $timeEntries = TimeEntry::all();
        $this->assertCount(4, $timeEntries);
        $timeEntry1 = $timeEntries->firstWhere('description', '');
        $this->assertNotNull($timeEntry1);
        $this->assertSame('', $timeEntry1->description);
        $this->assertSame('2024-03-04 10:23:52', $timeEntry1->start->toDateTimeString());
        $this->assertSame('2024-03-04 10:23:52', $timeEntry1->end->toDateTimeString());
        $this->assertFalse($timeEntry1->billable);
        $this->assertSame([$testScenario->tag1->getKey()], $timeEntry1->tags);
        $timeEntry2 = $timeEntries->firstWhere('description', 'Working hard');
        $this->assertNotNull($timeEntry2);
        $this->assertSame('Working hard', $timeEntry2->description);
        $this->assertSame('2024-03-04 10:23:00', $timeEntry2->start->toDateTimeString());
        $this->assertSame('2024-03-04 11:23:01', $timeEntry2->end->toDateTimeString());
        $this->assertTrue($timeEntry2->billable);
        $this->assertSame([], $timeEntry2->tags);
    }
}
