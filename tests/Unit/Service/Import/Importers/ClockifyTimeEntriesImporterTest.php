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
        $importer = new ClockifyTimeEntriesImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('clockify_time_entries_import_test_1.csv');

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

    public function test_import_of_test_with_special_characters_description_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyTimeEntriesImporter;
        $importer->init($organization);
        // Description: \\ 🔥 Special characters  """`!@#$%^&*()_+\-=\[\]{};':"\\|,.''<>\/?~ \\\
        $data = Storage::disk('testfiles')->get('clockify_time_entries_import_test_2.csv');

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $timeEntry = TimeEntry::first();
        $this->assertSame('\\\\ 🔥 Special characters  \'\'\'\'\'\'`!@#$%^&*()_+\-=\[\]{};\':\'\'\\\\|,.\'\'<>\/?~ \\\\\\', $timeEntry->description);
        $this->assertSame(1, $report->timeEntriesCreated);
        $this->assertSame(0, $report->tagsCreated);
        $this->assertSame(1, $report->tasksCreated);
        $this->assertSame(1, $report->usersCreated);
        $this->assertSame(1, $report->projectsCreated);
        $this->assertSame(1, $report->clientsCreated);
    }

    public function test_import_of_test_file_twice_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyTimeEntriesImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('clockify_time_entries_import_test_1.csv');
        $importer->importData($data, $timezone);
        $importer = new ClockifyTimeEntriesImporter;
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
}
