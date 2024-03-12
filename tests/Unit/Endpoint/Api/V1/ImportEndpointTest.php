<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Organization;
use App\Service\Import\Importers\ReportDto;
use App\Service\Import\ImportService;
use Laravel\Passport\Passport;
use Mockery\MockInterface;

class ImportEndpointTest extends ApiEndpointTestAbstract
{
    public function test_import_fails_if_user_does_not_have_permission()
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);

        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.import.import', ['organization' => $data->organization->id]), [
            'type' => 'toggl_time_entries',
            'data' => 'some data',
            'options' => [],
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_import_calls_import_service_if_user_has_permission(): void
    {
        // Arrange
        $user = $this->createUserWithPermission([
            'import',
        ]);
        $this->mock(ImportService::class, function (MockInterface $mock) use (&$user): void {
            $mock->shouldReceive('import')
                ->withArgs(function (Organization $organization, string $importerType, string $data) use (&$user): bool {
                    return $organization->is($user->organization) && $importerType === 'toggl_time_entries' && $data === 'some data';
                })
                ->andReturn(new ReportDto(
                    clientsCreated: 1,
                    projectsCreated: 2,
                    tasksCreated: 3,
                    timeEntriesCreated: 4,
                    tagsCreated: 5,
                    usersCreated: 6,
                ))
                ->once();
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->postJson(route('api.v1.import.import', ['organization' => $user->organization->id]), [
            'type' => 'toggl_time_entries',
            'data' => 'some data',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertExactJson([
            'report' => [
                'clients' => [
                    'created' => 1,
                ],
                'projects' => [
                    'created' => 2,
                ],
                'tasks' => [
                    'created' => 3,
                ],
                'time-entries' => [
                    'created' => 4,
                ],
                'tags' => [
                    'created' => 5,
                ],
                'users' => [
                    'created' => 6,
                ],
            ],
        ]);
    }
}
