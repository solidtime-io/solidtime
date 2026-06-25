<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Service\Import\Importers\ClockifyProjectsImporter;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\ImportException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ClockifyProjectsImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
class ClockifyProjectsImporterTest extends ImporterTestAbstract
{
    public function test_import_of_test_file_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyProjectsImporter;
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
        $importer = new ClockifyProjectsImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('clockify_projects_import_test_1.csv');
        $importer->importData($data, $timezone);
        $importer = new ClockifyProjectsImporter;
        $importer->init($organization);

        // Act
        $importer->importData($data, $timezone);

        // Assert
        $this->checkTestScenarioProjectsOnlyAfterImport();
    }

    public function test_import_sets_archived_at_based_on_status_column(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyProjectsImporter;
        $importer->init($organization);
        $data = Storage::disk('testfiles')->get('clockify_projects_import_test_2.csv');

        // Act
        $importer->importData($data, $timezone);

        // Assert
        $activeProject = Project::query()->where('organization_id', $organization->id)->where('name', 'Active Project')->firstOrFail();
        $this->assertNull($activeProject->archived_at);
        $this->assertFalse($activeProject->is_archived);

        $archivedProject = Project::query()->where('organization_id', $organization->id)->where('name', 'Archived Project')->firstOrFail();
        $this->assertNotNull($archivedProject->archived_at);
        $this->assertTrue($archivedProject->is_archived);
    }

    public function test_import_supports_renamed_tasks_column(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyProjectsImporter;
        $importer->init($organization);
        // Newer Clockify exports rename the "Task" column to "Tasks".
        $data = Storage::disk('testfiles')->get('clockify_projects_import_test_2.csv');

        // Act
        $importer->importData($data, $timezone);

        // Assert
        $activeProject = Project::query()->where('organization_id', $organization->id)->where('name', 'Active Project')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['Task 1', 'Task 2'],
            Task::query()->where('project_id', $activeProject->id)->pluck('name')->all(),
        );
    }

    public function test_import_supports_activities_column_alias_for_tasks(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new ClockifyProjectsImporter;
        $importer->init($organization);
        // Some Clockify exports name the tasks column "Activities".
        $data = Storage::disk('testfiles')->get('clockify_projects_import_test_3.csv');

        // Act
        $importer->importData($data, $timezone);

        // Assert
        $project = Project::query()->where('organization_id', $organization->id)->where('name', 'Project With Activities')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['Activity A', 'Activity B'],
            Task::query()->where('project_id', $project->id)->pluck('name')->all(),
        );
    }
}
