<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Enums\TimeEntryType;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Service\Import\Importers\ClockifyTimeEntriesImporter;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\ImportException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ClockifyTimeEntriesImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
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

    public function test_import_of_test_file_without_billable_works_and_defaults_to_non_billable(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyTimeEntriesImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('clockify_time_entries_import_test_4.csv');

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $testScenario = $this->checkTestScenarioAfterImportExcludingTimeEntries(false, true);
        $this->checkTimeEntries($testScenario, false, true);
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

    public function test_import_supports_activity_column_alias_for_task(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyTimeEntriesImporter;
        $importer->init($organization);
        // Some Clockify exports name the task column "Activity".
        $data = Storage::disk('testfiles')->get('clockify_time_entries_import_test_5.csv');

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $this->assertSame(2, $report->timeEntriesCreated);
        $this->assertSame(1, $report->tasksCreated);
    }

    public function test_import_of_test_file_without_client_column_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyTimeEntriesImporter;
        $importer->init($organization);
        // Newer Clockify exports no longer contain a "Client" column.
        $data = Storage::disk('testfiles')->get('clockify_time_entries_import_test_6.csv');

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $this->assertSame(2, $report->timeEntriesCreated);
        $this->assertSame(2, $report->projectsCreated);
        $this->assertSame(0, $report->clientsCreated);
    }

    public function test_import_of_test_file_with_client_column_but_missing_values_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyTimeEntriesImporter;
        $importer->init($organization);
        // Rows shorter than the header are padded with null by the CSV reader.
        $data = Storage::disk('testfiles')->get('clockify_time_entries_import_test_7.csv');

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $this->assertSame(1, $report->timeEntriesCreated);
        $this->assertSame(1, $report->projectsCreated);
        $this->assertSame(0, $report->clientsCreated);
    }

    public function test_import_fails_if_month_in_date_is_bigger_than_12(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyTimeEntriesImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('clockify_time_entries_import_test_3.csv');

        // Act
        try {
            $importer->importData($data, $timezone);
        } catch (ImportException $e) {
            // Assert
            $this->assertSame('Start date ("13/15/2024") is invalid, please select the correct date format before exporting from Clockify', $e->getMessage());

            return;
        }
        $this->fail();
    }

    public function test_import_creates_break_time_entry_when_type_is_break(): void
    {
        // Arrange
        // Clockify lets a break carry a project, task, tags and billable status, but those are
        // meaningless for non-work time. The break must import stripped of all of them, and must
        // NOT create the project/tag it referenced (which would be an orphan).
        $organization = Organization::factory()->create();
        $importer = new ClockifyTimeEntriesImporter;
        $importer->init($organization);
        $csv = <<<'CSV'
        "Project","Client","Description","Task","User","Group","Email","Tags","Billable","Start Date","Start Time","End Date","End Time","Duration (h)","Duration (decimal)","Billable Rate (USD)","Billable Amount (USD)","Type"
        "Break Project","Break Client","Lunch","Design","Peter Tester","","peter.test@email.test","Backend","Yes","03/04/2024","10:00:00 AM","03/04/2024","10:30:00 AM","00:30:00","0.50","0.00","0.00","Break"
        CSV;

        // Act
        $importer->importData($csv, 'Europe/Vienna');

        // Assert
        $timeEntry = TimeEntry::query()->firstOrFail();
        $this->assertSame(TimeEntryType::Break, $timeEntry->type);
        $this->assertFalse($timeEntry->billable);
        $this->assertNull($timeEntry->project_id);
        $this->assertNull($timeEntry->task_id);
        $this->assertNull($timeEntry->client_id);
        $this->assertSame([], $timeEntry->tags);
        // The break's project/tag/client must not have been created as orphans.
        $this->assertSame(0, Project::query()->count());
        $this->assertSame(0, Tag::query()->count());
        $this->assertSame(0, Client::query()->count());
    }
}
