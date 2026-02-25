<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\User;
use Laravel\Passport\Passport;

class UserEndpointTest extends ApiEndpointTestAbstract
{
    public function test_me_fails_when_not_authenticated(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.users.me'));

        // Assert
        $response->assertUnauthorized();
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_me_returns_information_about_the_current_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.users.me'));

        // Assert
        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'id' => $data->user->getKey(),
                'name' => $data->user->name,
                'email' => $data->user->email,
                'profile_photo_url' => $data->user->profile_photo_url,
                'timezone' => $data->user->timezone,
                'week_start' => $data->user->week_start->value,
            ],
        ]);
    }

    public function test_delete_fails_if_given_user_is_not_the_authenticated_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $otherData = $this->createUserWithPermission();
        Passport::actingAs($otherData->user);

        // Act
        $response = $this->deleteJson(route('api.v1.users.destroy', $data->user->getKey()));

        // Assert
        $response->assertForbidden();
    }

    public function test_delete_fails_if_not_authenticated(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();

        // Act
        $response = $this->deleteJson(route('api.v1.users.destroy', $data->user->getKey()));

        // Assert
        $response->assertUnauthorized();
    }

    public function test_delete_fails_if_user_does_not_exist(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.users.destroy', 'not-valid'));

        // Assert
        $response->assertNotFound();
    }

    public function test_delete_removes_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.users.destroy', $data->user->getKey()));

        // Assert
        $response->assertNoContent();
        $this->assertDatabaseMissing(User::class, ['id' => $data->user->getKey()]);
    }
}
