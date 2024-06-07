<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import;

use App\Models\Organization;
use App\Service\Import\Importers\ImporterProvider;
use App\Service\Import\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

#[CoversClass(ImportService::class)]
#[CoversClass(ImporterProvider::class)]
#[UsesClass(ImportService::class)]
class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_gets_importer_from_provider_runs_importer_and_returns_report(): void
    {
        // Arrange
        Storage::fake(config('filesystems.default'));
        $organization = Organization::factory()->create();
        $timezone = 'Europe/Vienna';
        $data = Storage::disk('testfiles')->get('toggl_time_entries_import_test_1.csv');

        // Act
        $importService = app(ImportService::class);
        $report = $importService->import($organization, 'toggl_time_entries', $data, $timezone);

        // Assert
        $this->assertSame(2, $report->timeEntriesCreated);
        $this->assertSame(2, $report->tagsCreated);
        $this->assertSame(1, $report->tasksCreated);
        $this->assertSame(1, $report->usersCreated);
        $this->assertSame(2, $report->projectsCreated);
        $this->assertSame(1, $report->clientsCreated);
    }
}
