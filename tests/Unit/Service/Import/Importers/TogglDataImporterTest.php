<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import\Importers;

use App\Models\Organization;
use App\Service\Import\Importers\DefaultImporter;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\TogglDataImporter;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use ZipArchive;

#[CoversClass(TogglDataImporter::class)]
#[CoversClass(ImportException::class)]
#[CoversClass(DefaultImporter::class)]
#[UsesClass(TogglDataImporter::class)]
class TogglDataImporterTest extends ImporterTestAbstract
{
    private function createTestZip(string $folder): string
    {
        $tempDir = TemporaryDirectory::make();
        $zipPath = $tempDir->path('test.zip');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        foreach (Storage::disk('testfiles')->allFiles($folder) as $file) {
            $zip->addFile(Storage::disk('testfiles')->path($file), Str::of($file)->after($folder.'/')->value());
        }
        $zip->close();

        return $zipPath;
    }

    public function test_import_throws_exception_if_data_is_not_zip(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $importer = new TogglDataImporter();
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
        $importer = new TogglDataImporter();
        $importer->init($organization);
        $data = file_get_contents($zipPath);

        // Act
        $importer->importData($data, $timezone);
        $report = $importer->getReport();

        // Assert
        $this->checkTestScenarioAfterImportExcludingTimeEntries(true);
        $this->assertSame(0, $report->timeEntriesCreated);
        $this->assertSame(2, $report->tagsCreated);
        $this->assertSame(1, $report->tasksCreated);
        $this->assertSame(1, $report->usersCreated);
        $this->assertSame(2, $report->projectsCreated);
        $this->assertSame(1, $report->clientsCreated);
    }

    public function test_import_of_test_file_twice_succeeds(): void
    {
        // Arrange
        $zipPath = $this->createTestZip('toggl_data_import_test_1');
        $timezone = 'Europe/Vienna';
        $organization = Organization::factory()->create();
        $importer = new TogglDataImporter();
        $importer->init($organization);
        $data = file_get_contents($zipPath);
        $importer->importData($data, $timezone);
        $importer = new TogglDataImporter();
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
}
