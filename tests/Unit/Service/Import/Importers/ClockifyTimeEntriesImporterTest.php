<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Models\Organization;
use App\Models\TimeEntry;
use App\Service\Import\Importers\ClockifyTimeEntriesImporter;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\ImportException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ClockifyTimeEntriesImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
#[UsesClass(ClockifyTimeEntriesImporter::class)]
class ClockifyTimeEntriesImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyTimeEntriesImporter();
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('clockify_time_entries_import_test_1.csv');

        // Act
        $importer->importData($data, $timezone);

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
        $this->assertTrue($timeEntry1->is_imported);
        $this->assertSame([$testScenario->tag1->getKey(), $testScenario->tag2->getKey()], $timeEntry1->tags);
        $timeEntry2 = $timeEntries->firstWhere('description', 'Working hard');
        $this->assertNotNull($timeEntry2);
        $this->assertSame('Working hard', $timeEntry2->description);
        $this->assertSame('2024-03-04 09:23:00', $timeEntry2->start->toDateTimeString());
        $this->assertSame('2024-03-04 10:23:01', $timeEntry2->end->toDateTimeString());
        $this->assertTrue($timeEntry2->billable);
        $this->assertTrue($timeEntry2->is_imported);
        $this->assertSame([], $timeEntry2->tags);
    }

    public function test_import_of_test_file_twice_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyTimeEntriesImporter();
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('clockify_time_entries_import_test_1.csv');
        $importer->importData($data, $timezone);
        $importer = new ClockifyTimeEntriesImporter();
        $importer->init($organization);

        // Act
        $importer->importData($data, $timezone);

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
        $this->assertTrue($timeEntry1->is_imported);
        $this->assertSame([$testScenario->tag1->getKey(), $testScenario->tag2->getKey()], $timeEntry1->tags);
        $timeEntry2 = $timeEntries->firstWhere('description', 'Working hard');
        $this->assertNotNull($timeEntry2);
        $this->assertSame('Working hard', $timeEntry2->description);
        $this->assertSame('2024-03-04 09:23:00', $timeEntry2->start->toDateTimeString());
        $this->assertSame('2024-03-04 10:23:01', $timeEntry2->end->toDateTimeString());
        $this->assertTrue($timeEntry2->billable);
        $this->assertTrue($timeEntry2->is_imported);
        $this->assertSame([], $timeEntry2->tags);
    }
}
