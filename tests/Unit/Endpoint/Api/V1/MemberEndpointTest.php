<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Laravel\Passport\Passport;

class MemberEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_fails_if_user_has_no_permission_to_view_members(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.members.index', $data->organization->id));

        // Assert
        $response->assertStatus(403);
    }

    public function test_index_returns_members_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:view',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.members.index', $data->organization->getKey()));

        // Assert
        $response->assertStatus(200);
    }

    public function test_update_member_fails_if_user_has_no_permission_to_update_members(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $data->member->getKey()]), [
            'billable_rate' => 10001,
            'role' => 'employee',
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_update_member_fails_if_member_is_not_part_of_org(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
        ]);
        $otherData = $this->createUserWithPermission([
            'members:update',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $otherData->member->getKey()]), [
            'billable_rate' => 10001,
            'role' => 'employee',
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_update_member_succeeds_if_data_is_valid(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->id, $data->member]), [
            'billable_rate' => 10001,
            'role' => 'employee',
        ]);

        // Assert
        $response->assertStatus(200);
        $member = $data->member;
        $member->refresh();
        $this->assertSame(10001, $member->billable_rate);
        $this->assertSame('employee', $member->role);
    }

    public function test_invite_placeholder_succeeds_if_data_is_valid(): void
    {
        $data = $this->createUserWithPermission([
            'members:invite-placeholder',
        ], true);
        $user = User::factory()->create([
            'is_placeholder' => true,
        ]);
        $member = Membership::factory()->forUser($user)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.invite-placeholder', [
            'organization' => $data->organization->getKey(),
            'member' => $member->getKey(),
        ]));

        // Assert
        $response->assertValid();
        $response->assertStatus(204);
    }

    public function test_destroy_member_fails_if_user_has_no_permission_to_delete_members(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $data->member->getKey()]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_destroy_member_fails_if_member_is_not_part_of_org(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        $otherData = $this->createUserWithPermission([
            'members:delete',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $otherData->member->getKey()]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_destroy_member_succeeds_if_data_is_valid(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $data->member->getKey()]));

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing(Membership::class, [
            'id' => $data->member->getKey(),
        ]);
    }

    public function test_invite_placeholder_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $user = User::factory()->create([
            'is_placeholder' => true,
        ]);
        $member = Membership::factory()->forUser($user)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.invite-placeholder', [
            'organization' => $data->organization->id,
            'member' => $member->id,
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_invite_placeholder_fails_if_user_is_not_part_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:invite-placeholder',
        ]);
        $otherOrganization = Organization::factory()->create();
        $user = User::factory()->create([
            'is_placeholder' => true,
        ]);
        $member = Membership::factory()->forUser($user)->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.invite-placeholder', [
            'organization' => $data->organization->id,
            'member' => $member->id,
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_invite_placeholder_returns_400_if_user_is_not_placeholder(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:invite-placeholder',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.invite-placeholder', [
            'organization' => $data->organization->id,
            'member' => $data->member->id,
        ]));

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'user_not_placeholder',
            'message' => 'The given user is not a placeholder',
        ]);
    }
}
