<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Jobs\RecalculateSpentTimeForProject;
use App\Jobs\RecalculateSpentTimeForTask;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\TogglTimeEntriesImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TogglTimeEntriesImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
class TogglTimeEntriesImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        Queue::fake([
            RecalculateSpentTimeForProject::class,
            RecalculateSpentTimeForTask::class,
        ]);
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new TogglTimeEntriesImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('toggl_time_entries_import_test_1.csv');

        // Act
        DB::enableQueryLog();
        DB::flushQueryLog();
        $importer->importData($data, $timezone);
        $report = $importer->getReport();
        $queryLog = DB::getQueryLog();

        // Assert
        $this->assertCount(22, $queryLog);
        $testScenario = $this->checkTestScenarioAfterImportExcludingTimeEntries();
        $this->checkTimeEntries($testScenario);
        $this->assertSame(2, $report->timeEntriesCreated);
        $this->assertSame(2, $report->tagsCreated);
        $this->assertSame(1, $report->tasksCreated);
        $this->assertSame(1, $report->usersCreated);
        $this->assertSame(2, $report->projectsCreated);
        $this->assertSame(1, $report->clientsCreated);
        Queue::assertPushed(RecalculateSpentTimeForProject::class, 2);
        Queue::assertPushed(RecalculateSpentTimeForTask::class, 1);
    }

    public function test_import_of_test_with_special_characters_description_succeeds(): void
    {
        // Arrange
        Queue::fake([
            RecalculateSpentTimeForProject::class,
            RecalculateSpentTimeForTask::class,
        ]);
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new TogglTimeEntriesImporter;
        $importer->init($organization);
        // Description: \\ 🔥 Special characters  """`!@#$%^&*()_+\-=\[\]{};':"\\|,.''<>\/?~ \\\
        $data = Storage::disk('testfiles')->get('toggl_time_entries_import_test_2.csv');

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $timeEntry = TimeEntry::first();
        $this->assertSame('\\\\ 🔥 Special characters  """`!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.\'\'<>\/?~ \\\\\\', $timeEntry->description);
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
        $importer = new TogglTimeEntriesImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('toggl_time_entries_import_test_1.csv');
        $importer->importData($data, $timezone);
        $importer = new TogglTimeEntriesImporter;
        $importer->init($organization);
        Queue::fake([
            RecalculateSpentTimeForProject::class,
            RecalculateSpentTimeForTask::class,
        ]);

        // Act
        DB::enableQueryLog();
        DB::flushQueryLog();
        $importer->importData($data, $timezone);
        $report = $importer->getReport();
        $queryLog = DB::getQueryLog();

        // Assert
        $this->assertCount(14, $queryLog);
        $testScenario = $this->checkTestScenarioAfterImportExcludingTimeEntries();
        $this->checkTimeEntries($testScenario, true);
        $this->assertSame(2, $report->timeEntriesCreated);
        $this->assertSame(0, $report->tagsCreated);
        $this->assertSame(0, $report->tasksCreated);
        $this->assertSame(0, $report->usersCreated);
        $this->assertSame(0, $report->projectsCreated);
        $this->assertSame(0, $report->clientsCreated);
        Queue::assertPushed(RecalculateSpentTimeForProject::class, 2);
        Queue::assertPushed(RecalculateSpentTimeForTask::class, 1);
    }
}
