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
use Spatie\TemporaryDirectory\TemporaryDirectory;
use ZipArchive;

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

    public function test_import_with_path_traversal_in_project_id_is_rejected_without_touching_the_filesystem(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $importer = new TogglDataImporter;
        $importer->init($organization);

        $markerDir = sys_get_temp_dir().'/solidtime_path_traversal_'.uniqid();
        $this->assertDirectoryDoesNotExist($markerDir);
        // Enough "../" to reach the filesystem root from any temp location, then
        // back down into the attacker-chosen marker directory. The importer
        // appends ".json", so the parent directory Spatie's TemporaryDirectory
        // would auto-create for the resolved path is exactly $markerDir.
        $traversalId = str_repeat('../', 40).ltrim($markerDir, '/').'/probe';
        $data = file_get_contents($this->buildTogglZipWithProjectId($traversalId));

        // Act
        try {
            $importer->importData($data, 'Europe/Vienna');
            $this->fail('Expected ImportException was not thrown');
        } catch (ImportException $e) {
            // Rejected by the identifier guard, not by a downstream
            // "missing in ZIP" error (which would mean the sink was reached
            // and the directory had already been created).
            $this->assertSame('Invalid identifier in import data', $e->getMessage());
        }

        // Assert: no directory was created outside the import sandbox.
        $this->assertDirectoryDoesNotExist($markerDir);
    }

    public function test_import_with_valid_numeric_project_id_is_accepted(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $importer = new TogglDataImporter;
        $importer->init($organization);
        // A legitimate Toggl numeric id must still pass the guard. The
        // projects_users file is intentionally absent, so the importer fails
        // with the ordinary "missing in ZIP" error rather than the guard error.
        $data = file_get_contents($this->buildTogglZipWithProjectId(402));

        // Act
        try {
            $importer->importData($data, 'Europe/Vienna');
            $this->fail('Expected ImportException was not thrown');
        } catch (ImportException $e) {
            // Assert: the numeric id passed the guard and reached the ZIP
            // content check (proving valid data is not rejected).
            $this->assertSame('File "projects_users/402.json" missing in ZIP', $e->getMessage());
        }
    }

    private function buildTogglZipWithProjectId(mixed $projectId): string
    {
        $tempDir = TemporaryDirectory::make();
        $zipPath = $tempDir->path('traversal.zip');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('clients.json', '[]');
        $zip->addFromString('tags.json', '[]');
        $zip->addFromString('workspace_users.json', '[]');
        $zip->addFromString('projects.json', (string) json_encode([[
            'id' => $projectId,
            'client_id' => null,
            'color' => '#ff0000',
            'billable' => false,
            'is_private' => false,
            'rate' => null,
            'name' => 'Traversal',
        ]]));
        $zip->close();

        return $zipPath;
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
