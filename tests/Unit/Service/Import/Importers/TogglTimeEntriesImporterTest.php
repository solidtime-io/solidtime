<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Models\Organization;
use App\Models\TimeEntry;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\TogglTimeEntriesImporter;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(TogglTimeEntriesImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
#[UsesClass(TogglTimeEntriesImporter::class)]
class TogglTimeEntriesImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new TogglTimeEntriesImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('toggl_time_entries_import_test_1.csv');

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $testScenario = $this->checkTestScenarioAfterImportExcludingTimeEntries();
        $timeEntries = TimeEntry::all();
        $this->assertCount(2, $timeEntries);
        $timeEntry1 = $timeEntries->firstWhere('description', '');
        $this->assertNotNull($timeEntry1);
        $this->assertSame('', $timeEntry1->description);
        $this->assertSame('2024-03-04 09:23:52', $timeEntry1->start->toDateTimeString());
        $this->assertSame('2024-03-04 09:23:52', $timeEntry1->end->toDateTimeString());
        $this->assertFalse($timeEntry1->billable);
        $this->assertSame([$testScenario->tag1->getKey(), $testScenario->tag2->getKey()], $timeEntry1->tags);
        $timeEntry2 = $timeEntries->firstWhere('description', 'Working hard');
        $this->assertNotNull($timeEntry2);
        $this->assertSame('Working hard', $timeEntry2->description);
        $this->assertSame('2024-03-04 09:23:00', $timeEntry2->start->toDateTimeString());
        $this->assertSame('2024-03-04 10:23:01', $timeEntry2->end->toDateTimeString());
        $this->assertTrue($timeEntry2->billable);
        $this->assertSame([], $timeEntry2->tags);
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
        $importer = new TogglTimeEntriesImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('toggl_time_entries_import_test_1.csv');
        $importer->importData($data, $timezone);
        $importer = new TogglTimeEntriesImporter;
        $importer->init($organization);

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $testScenario = $this->checkTestScenarioAfterImportExcludingTimeEntries();
        $timeEntries = TimeEntry::all();
        $this->assertCount(4, $timeEntries);
        $timeEntry1 = $timeEntries->firstWhere('description', '');
        $this->assertNotNull($timeEntry1);
        $this->assertSame('', $timeEntry1->description);
        $this->assertSame('2024-03-04 09:23:52', $timeEntry1->start->toDateTimeString());
        $this->assertSame('2024-03-04 09:23:52', $timeEntry1->end->toDateTimeString());
        $this->assertFalse($timeEntry1->billable);
        $this->assertSame([$testScenario->tag1->getKey(), $testScenario->tag2->getKey()], $timeEntry1->tags);
        $timeEntry2 = $timeEntries->firstWhere('description', 'Working hard');
        $this->assertNotNull($timeEntry2);
        $this->assertSame('Working hard', $timeEntry2->description);
        $this->assertSame('2024-03-04 09:23:00', $timeEntry2->start->toDateTimeString());
        $this->assertSame('2024-03-04 10:23:01', $timeEntry2->end->toDateTimeString());
        $this->assertTrue($timeEntry2->billable);
        $this->assertSame([], $timeEntry2->tags);
        $this->assertSame(2, $report->timeEntriesCreated);
        $this->assertSame(0, $report->tagsCreated);
        $this->assertSame(0, $report->tasksCreated);
        $this->assertSame(0, $report->usersCreated);
        $this->assertSame(0, $report->projectsCreated);
        $this->assertSame(0, $report->clientsCreated);
    }
}
