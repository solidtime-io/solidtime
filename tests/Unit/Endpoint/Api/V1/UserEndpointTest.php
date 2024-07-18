<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

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
}
