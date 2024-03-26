<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Organization;
use App\Models\User;
use Laravel\Passport\Passport;

class MemberEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_returns_members_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'users:view',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.users.index', $data->organization->id));

        // Assert
        $response->assertStatus(200);
    }

    public function test_invite_placeholder_succeeds_if_data_is_valid(): void
    {
        $data = $this->createUserWithPermission([
            'users:invite-placeholder',
        ], true);
        $user = User::factory()->create([
            'is_placeholder' => true,
        ]);
        $data->organization->users()->attach($user, [
            'role' => 'placeholder',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.users.invite-placeholder', [
            'organization' => $data->organization->id,
            'user' => $user->id,
        ]));

        // Assert
        $response->assertStatus(204);
    }

    public function test_invite_placeholder_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $user = User::factory()->create([
            'is_placeholder' => true,
        ]);
        $data->organization->users()->attach($user);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.users.invite-placeholder', ['organization' => $data->organization->id, 'user' => $user->id]));

        // Assert
        $response->assertForbidden();
    }

    public function test_invite_placeholder_fails_if_user_is_not_part_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'users:invite-placeholder',
        ]);
        $otherOrganization = Organization::factory()->create();
        $user = User::factory()->create([
            'is_placeholder' => true,
        ]);
        $otherOrganization->users()->attach($user);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.users.invite-placeholder', ['organization' => $data->organization->id, 'user' => $user->id]));

        // Assert
        $response->assertForbidden();
    }

    public function test_invite_placeholder_returns_400_if_user_is_not_placeholder(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'users:invite-placeholder',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.users.invite-placeholder', ['organization' => $data->organization->id, 'user' => $data->user->id]));

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'user_not_placeholder',
            'message' => 'The given user is not a placeholder',
        ]);
    }
}
