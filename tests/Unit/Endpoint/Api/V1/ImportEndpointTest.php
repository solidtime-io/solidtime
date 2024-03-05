<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Organization;
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
        $response = $this->postJson(route('api.v1.import', ['organization' => $data->organization->id]), [
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
                ->withArgs(function (Organization $organization, string $importerType, string $data, array $options) use (&$user): bool {
                    return $organization->is($user->organization) && $importerType === 'toggl_time_entries' && $data === 'some data' && $options === [];
                })
                ->once();
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->postJson(route('api.v1.import.import', ['organization' => $user->organization->id]), [
            'type' => 'toggl_time_entries',
            'data' => 'some data',
            'options' => [],
        ]);

        // Assert
        $response->assertStatus(200);
    }
}
