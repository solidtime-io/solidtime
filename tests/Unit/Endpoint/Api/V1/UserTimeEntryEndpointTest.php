<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\TimeEntry;
use Laravel\Passport\Passport;

class UserTimeEntryEndpointTest extends ApiEndpointTestAbstract
{
    public function test_my_active_endpoint_returns_unauthorized_if_user_is_not_logged_in(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);

        // Act
        $response = $this->getJson(route('api.v1.users.time-entries.my-active'));

        // Assert
        $response->assertUnauthorized();
    }

    public function test_my_active_endpoint_returns_current_time_entry_of_logged_in_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $activeTimeEntry = TimeEntry::factory()->forUser($data->user)->active()->create();
        $inactiveTimeEntry = TimeEntry::factory()->forUser($data->user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.users.time-entries.my-active'));

        // Assert
        $response->assertSuccessful();
    }

    public function test_my_active_endpoint_returns_not_found_if_user_has_no_active_time_entry(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $inactiveTimeEntry = TimeEntry::factory()->forUser($data->user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.users.time-entries.my-active'));

        // Assert
        $response->assertNotFound();
    }
}
