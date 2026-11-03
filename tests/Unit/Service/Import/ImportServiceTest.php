<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Import;

use App\Enums\ImportSource;
use App\Enums\ImportStatus;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\Import\Importers\ImporterContract;
use App\Service\Import\Importers\ImporterProvider;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\ImportService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;

#[CoversClass(ImportService::class)]
#[CoversClass(ImporterProvider::class)]
class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_file_import_stores_file_creates_pending_import_and_dispatches_job(): void
    {
        // Arrange
        Queue::fake();
        Storage::fake(config('filesystems.private'));
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $data = Storage::disk('testfiles')->get('toggl_time_entries_import_test_1.csv');

        // Act
        $import = app(ImportService::class)->createFileImport($organization, $user, 'toggl_time_entries', $data, 'Europe/Vienna');

        // Assert
        $import->refresh();
        $this->assertSame('toggl_time_entries', $import->type);
        $this->assertSame(ImportSource::File, $import->source);
        $this->assertSame(ImportStatus::Pending, $import->status);
        $this->assertSame('Europe/Vienna', $import->timezone);
        $this->assertSame($organization->getKey(), $import->organization_id);
        $this->assertSame($user->getKey(), $import->user_id);
        $this->assertSame('import/'.$organization->getKey().'/'.$import->getKey(), $import->file_path);
        $this->assertSame($data, Storage::disk(config('filesystems.private'))->get($import->file_path));
        Queue::assertPushed(ProcessImport::class, fn (ProcessImport $job): bool => $job->import->is($import));
    }

    public function test_create_file_import_throws_exception_if_import_is_already_in_progress(): void
    {
        // Arrange
        Queue::fake();
        Storage::fake(config('filesystems.private'));
        $organization = Organization::factory()->create();
        Import::factory()->forOrganization($organization)->running()->create();

        // Act
        try {
            app(ImportService::class)->createFileImport($organization, null, 'toggl_time_entries', 'data', 'Europe/Vienna');
            $this->fail('Expected ImportException');
        } catch (ImportException $exception) {
            // Assert
            $this->assertSame('Import is already in progress', $exception->getMessage());
        }
        $this->assertSame(1, Import::query()->count());
        $this->assertSame([], Storage::disk(config('filesystems.private'))->allFiles());
        Queue::assertNothingPushed();
    }

    public function test_create_file_import_allows_new_import_if_previous_imports_are_finished(): void
    {
        // Arrange
        Queue::fake();
        Storage::fake(config('filesystems.private'));
        $organization = Organization::factory()->create();
        Import::factory()->forOrganization($organization)->completed()->create();
        Import::factory()->forOrganization($organization)->failed()->create();

        // Act
        $import = app(ImportService::class)->createFileImport($organization, null, 'toggl_time_entries', 'data', 'Europe/Vienna');

        // Assert
        $this->assertSame(ImportStatus::Pending, $import->status);
        $this->assertSame(3, Import::query()->count());
    }

    public function test_create_file_import_throws_exception_if_importer_type_is_unknown(): void
    {
        // Arrange
        Queue::fake();
        Storage::fake(config('filesystems.private'));
        $organization = Organization::factory()->create();

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        app(ImportService::class)->createFileImport($organization, null, 'unknown', 'data', 'Europe/Vienna');
    }

    public function test_database_allows_only_one_active_import_per_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        Import::factory()->forOrganization($organization)->create();

        // Assert
        $this->expectException(UniqueConstraintViolationException::class);

        // Act
        Import::factory()->forOrganization($organization)->running()->create();
    }

    public function test_process_runs_importer_and_stores_report(): void
    {
        // Arrange
        Storage::fake(config('filesystems.private'));
        $organization = Organization::factory()->create();
        $data = Storage::disk('testfiles')->get('toggl_time_entries_import_test_1.csv');
        Storage::disk(config('filesystems.private'))->put('import/test', $data);
        $import = Import::factory()->forOrganization($organization)->create([
            'file_path' => 'import/test',
            'credentials' => ['token' => 'secret'],
        ]);

        // Act
        app(ImportService::class)->process($import);

        // Assert
        $import->refresh();
        $this->assertSame(ImportStatus::Completed, $import->status);
        $this->assertNotNull($import->started_at);
        $this->assertNotNull($import->finished_at);
        $this->assertNull($import->error_message);
        $this->assertNull($import->credentials);
        // Note: jsonb does not preserve the key order
        $this->assertEquals([
            'clients' => ['created' => 1],
            'projects' => ['created' => 2],
            'tasks' => ['created' => 1],
            'time_entries' => ['created' => 2],
            'tags' => ['created' => 2],
            'users' => ['created' => 1],
        ], $import->report);
        $this->assertSame(2, TimeEntry::query()->whereBelongsTo($organization, 'organization')->count());
    }

    public function test_process_marks_import_as_failed_with_message_if_importer_throws_import_exception(): void
    {
        // Arrange
        Storage::fake(config('filesystems.private'));
        $organization = Organization::factory()->create();
        Storage::disk(config('filesystems.private'))->put('import/test', 'Invalid CSV data');
        $import = Import::factory()->forOrganization($organization)->create([
            'file_path' => 'import/test',
            'credentials' => ['token' => 'secret'],
        ]);

        // Act
        app(ImportService::class)->process($import);

        // Assert
        $import->refresh();
        $this->assertSame(ImportStatus::Failed, $import->status);
        $this->assertNotNull($import->error_message);
        $this->assertNotSame('Unexpected error during import', $import->error_message);
        $this->assertNull($import->report);
        $this->assertNull($import->credentials);
        $this->assertNotNull($import->finished_at);
    }

    public function test_process_marks_import_as_failed_with_generic_message_and_rolls_back_on_unexpected_error(): void
    {
        // Arrange
        Storage::fake(config('filesystems.private'));
        $organization = Organization::factory()->create();
        Storage::disk(config('filesystems.private'))->put('import/test', 'data');
        $import = Import::factory()->forOrganization($organization)->create([
            'file_path' => 'import/test',
        ]);
        $importer = $this->mock(ImporterContract::class, function (MockInterface $mock) use ($organization): void {
            $mock->shouldReceive('init')->once();
            $mock->shouldReceive('importData')
                ->once()
                ->andReturnUsing(function () use ($organization): void {
                    TimeEntry::factory()->forOrganization($organization)->create();
                    throw new RuntimeException('Something went wrong');
                });
        });
        $this->mock(ImporterProvider::class, function (MockInterface $mock) use ($importer): void {
            $mock->shouldReceive('getImporter')->with('toggl_time_entries')->andReturn($importer);
        });

        // Act
        app(ImportService::class)->process($import);

        // Assert
        $import->refresh();
        $this->assertSame(ImportStatus::Failed, $import->status);
        $this->assertSame('Unexpected error during import', $import->error_message);
        $this->assertSame(0, TimeEntry::query()->count());
    }

    public function test_process_marks_import_as_failed_if_file_does_not_exist(): void
    {
        // Arrange
        Storage::fake(config('filesystems.private'));
        $import = Import::factory()->create([
            'file_path' => 'import/does-not-exist',
        ]);

        // Act
        app(ImportService::class)->process($import);

        // Assert
        $import->refresh();
        $this->assertSame(ImportStatus::Failed, $import->status);
        $this->assertSame('Import file does not exist anymore', $import->error_message);
    }

    public function test_process_skips_import_that_is_not_pending(): void
    {
        // Arrange
        $import = Import::factory()->completed()->create();
        $finishedAt = $import->finished_at;

        // Act
        app(ImportService::class)->process($import);

        // Assert
        $import->refresh();
        $this->assertSame(ImportStatus::Completed, $import->status);
        $this->assertEquals($finishedAt, $import->finished_at);
    }

    public function test_mark_as_failed_sets_status_message_and_removes_credentials(): void
    {
        // Arrange
        $import = Import::factory()->running()->create([
            'credentials' => ['token' => 'secret'],
        ]);

        // Act
        app(ImportService::class)->markAsFailed($import, 'Some error');

        // Assert
        $import->refresh();
        $this->assertSame(ImportStatus::Failed, $import->status);
        $this->assertSame('Some error', $import->error_message);
        $this->assertNull($import->credentials);
        $this->assertNotNull($import->finished_at);
    }
}
