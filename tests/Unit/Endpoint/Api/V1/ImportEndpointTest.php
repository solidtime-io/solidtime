<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\ImportStatus;
use App\Http\Controllers\Api\V1\ImportController;
use App\Models\Import;
use App\Models\Organization;
use App\Models\User;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\ImportService;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(ImportController::class)]
class ImportEndpointTest extends ApiEndpointTestAbstract
{
    public function test_importers_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();

        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.import.importers', ['organization' => $data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_importers_returns_importers_if_user_has_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'import',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.import.importers', ['organization' => $data->organization->getKey()]));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                [
                    'key',
                    'name',
                    'description',
                ],
            ],
        ]);
        $toggleTimeEntries = collect($response->json('data'))->where('key', 'toggl_time_entries')->first();
        $this->assertSame('toggl_time_entries', $toggleTimeEntries['key']);
        $this->assertSame('Toggl Time Entries', $toggleTimeEntries['name']);
        $this->assertSame(__('importer.toggl_time_entries.description'), $toggleTimeEntries['description']);
    }

    public function test_import_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();

        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.import.import', ['organization' => $data->organization->getKey()]), [
            'type' => 'toggl_time_entries',
            'data' => base64_encode('some data'),
            'options' => [],
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_import_fails_if_data_can_not_be_base64_decoded(): void
    {
        // Arrange
        $user = $this->createUserWithPermission([
            'import',
        ]);
        Passport::actingAs($user->user);

        // Act
        $response = $this->postJson(route('api.v1.import.import', ['organization' => $user->organization->getKey()]), [
            'type' => 'toggl_time_entries',
            'data' => 'some invalid data ...',
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'Invalid base64 encoded data',
        ]);
    }

    public function test_import_fails_if_data_exceeds_maximum_size(): void
    {
        // Arrange
        config(['import.max_data_size' => 16]);
        $user = $this->createUserWithPermission([
            'import',
        ]);
        $this->mock(ImportService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createFileImport');
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->postJson(route('api.v1.import.import', ['organization' => $user->organization->getKey()]), [
            'type' => 'toggl_time_entries',
            'data' => base64_encode(str_repeat('a', 15)),
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['data']);
    }

    public function test_import_returns_error_message_if_import_can_not_be_created(): void
    {
        // Arrange
        $user = $this->createUserWithPermission([
            'import',
        ]);
        $this->mock(ImportService::class, function (MockInterface $mock) use (&$user): void {
            $mock->shouldReceive('createFileImport')
                ->withArgs(function (Organization $organization, ?User $importUser, string $importerType, string $data) use (&$user): bool {
                    return $organization->is($user->organization) && $importUser->is($user->user) && $importerType === 'toggl_time_entries' && $data === 'some data';
                })
                ->andThrow(new ImportException('Import is already in progress'))
                ->once();
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->postJson(route('api.v1.import.import', ['organization' => $user->organization->getKey()]), [
            'type' => 'toggl_time_entries',
            'data' => base64_encode('some data'),
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'Import is already in progress',
        ]);
    }

    public function test_import_creates_import_via_import_service_and_returns_it(): void
    {
        // Arrange
        $user = $this->createUserWithPermission([
            'import',
        ]);
        $user->user->timezone = 'America/New_York';
        $user->user->save();
        $import = Import::factory()->forOrganization($user->organization)->forUser($user->user)->create();
        $this->mock(ImportService::class, function (MockInterface $mock) use (&$user, $import): void {
            $mock->shouldReceive('createFileImport')
                ->withArgs(function (Organization $organization, ?User $importUser, string $importerType, string $data, string $timezone) use (&$user): bool {
                    return $organization->is($user->organization) && $importUser->is($user->user) &&
                        $importerType === 'toggl_time_entries' && $data === 'some data' && $timezone === 'America/New_York';
                })
                ->andReturn($import)
                ->once();
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->postJson(route('api.v1.import.import', ['organization' => $user->organization->getKey()]), [
            'type' => 'toggl_time_entries',
            'data' => base64_encode('some data'),
        ]);

        // Assert
        $response->assertStatus(202);
        $response->assertJsonPath('data.id', $import->getKey());
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonPath('data.report', null);
    }

    public function test_import_processes_import_if_queue_is_sync(): void
    {
        // Arrange
        Storage::fake(config('filesystems.private'));
        $user = $this->createUserWithPermission([
            'import',
        ]);
        Passport::actingAs($user->user);
        $data = Storage::disk('testfiles')->get('toggl_time_entries_import_test_1.csv');

        // Act
        $response = $this->postJson(route('api.v1.import.import', ['organization' => $user->organization->getKey()]), [
            'type' => 'toggl_time_entries',
            'data' => base64_encode($data),
        ]);

        // Assert
        $response->assertStatus(202);
        $response->assertJsonPath('data.type', 'toggl_time_entries');
        $response->assertJsonPath('data.source', 'file');
        $response->assertJsonPath('data.status', 'completed');
        $response->assertJsonPath('data.user_id', $user->user->getKey());
        $response->assertJsonPath('data.report.time_entries.created', 2);
        $response->assertJsonPath('data.error_message', null);
        $response->assertJsonMissingPath('data.credentials');
        $response->assertJsonMissingPath('data.file_path');
    }

    public function test_imports_index_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.import.imports.index', ['organization' => $data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_imports_index_returns_imports_of_organization_newest_first(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'import',
        ]);
        $olderImport = Import::factory()->forOrganization($data->organization)->failed()->create([
            'created_at' => now()->subDay(),
        ]);
        $newerImport = Import::factory()->forOrganization($data->organization)->completed()->create([
            'created_at' => now(),
        ]);
        Import::factory()->completed()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.import.imports.index', ['organization' => $data->organization->getKey()]));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $newerImport->getKey());
        $response->assertJsonPath('data.0.status', ImportStatus::Completed->value);
        $response->assertJsonPath('data.1.id', $olderImport->getKey());
        $response->assertJsonPath('data.1.error_message', 'Import failed');
    }

    public function test_imports_show_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $import = Import::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.import.imports.show', [
            'organization' => $data->organization->getKey(),
            'import' => $import->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_imports_show_fails_if_import_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'import',
        ]);
        $import = Import::factory()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.import.imports.show', [
            'organization' => $data->organization->getKey(),
            'import' => $import->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_imports_show_returns_import(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'import',
        ]);
        $import = Import::factory()->forOrganization($data->organization)->forUser($data->user)->completed()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.import.imports.show', [
            'organization' => $data->organization->getKey(),
            'import' => $import->getKey(),
        ]));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.id', $import->getKey());
        $response->assertJsonPath('data.status', 'completed');
        $response->assertJsonPath('data.user_id', $data->user->getKey());
        $response->assertJsonPath('data.report.users.created', 6);
    }
}
