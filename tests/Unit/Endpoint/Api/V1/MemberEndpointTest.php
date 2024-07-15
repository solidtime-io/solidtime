<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\Role;
use App\Http\Controllers\Api\V1\MemberController;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\BillableRateService;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(MemberController::class)]
class MemberEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_fails_if_user_has_no_permission_to_view_members(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
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
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $data->member->getKey()]), [
            'billable_rate' => 10001,
            'role' => Role::Employee->value,
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
            'role' => Role::Employee->value,
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
        $member = Member::factory()->forOrganization($data->organization)->withBillableRate()->role(Role::Admin)->create();
        $this->assertBillableRateServiceIsUnused();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->id, $member]), [
            'billable_rate' => $member->billable_rate,
            'role' => Role::Employee->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $oldBillableRate = $member->billable_rate;
        $member->refresh();
        $this->assertSame($oldBillableRate, $member->billable_rate);
        $this->assertSame(Role::Employee->value, $member->role);
    }

    public function test_update_member_can_update_billable_rate_of_member_and_update_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
        ]);
        $this->mock(BillableRateService::class, function (MockInterface $mock) use ($data) {
            $mock->shouldReceive('updateTimeEntriesBillableRateForMember')
                ->once()
                ->withArgs(fn (Member $memberArg) => $memberArg->is($data->member) && $memberArg->billable_rate === 10001);
        });
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $data->member]), [
            'billable_rate' => 10001,
        ]);

        // Assert
        $response->assertStatus(200);
        $member = $data->member;
        $member->refresh();
        $this->assertSame(10001, $member->billable_rate);
    }

    public function test_update_member_can_update_role(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
        ]);
        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->forUser($otherUser)->forOrganization($data->organization)->role(Role::Employee)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $otherMember->getKey()]), [
            'role' => Role::Admin->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $otherMember->refresh();
        $this->assertSame(Role::Admin->value, $otherMember->role);
    }

    public function test_update_member_allows_role_owner_in_request_if_that_would_not_the_role(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $data->ownerMember->getKey()]), [
            'role' => Role::Owner->value,
        ]);

        // Assert
        $response->assertStatus(200);
    }

    public function test_update_member_fails_if_user_tries_to_change_role_to_owner(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
        ]);
        $member = Member::factory()->forOrganization($data->organization)->role(Role::Employee)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $member->getKey()]), [
            'role' => Role::Owner->value,
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Only owner can change ownership');
    }

    public function test_update_member_fails_if_user_tries_to_change_role_of_the_current_owner(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
            'members:change-ownership',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $data->ownerMember->getKey()]), [
            'role' => Role::Admin->value,
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Organization needs at least one owner');
    }

    public function test_update_member_can_change_role_to_everything_expect_owner_with_the_member_update_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
        ]);
        $member = Member::factory()->forOrganization($data->organization)->role(Role::Employee)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $member->getKey()]), [
            'role' => Role::Admin->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $member->refresh();
        $this->assertSame(Role::Admin->value, $member->role);
    }

    public function test_update_member_can_change_role_to_owner_if_auth_user_has_change_ownership_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
            'members:change-ownership',
        ]);
        $oldOwner = $data->ownerMember;
        $organization = $data->organization;
        $member = Member::factory()->forOrganization($data->organization)->role(Role::Employee)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $member->getKey()]), [
            'role' => Role::Owner->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $member->refresh();
        $organization->refresh();
        $oldOwner->refresh();
        $this->assertSame(Role::Owner->value, $member->role);
        $this->assertSame($member->user_id, $organization->user_id);
        $this->assertSame(Role::Admin->value, $oldOwner->role);
    }

    public function test_update_member_role_fails_if_role_is_placeholder(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
        ]);
        $member = Member::factory()->forOrganization($data->organization)->role(Role::Employee)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $member->getKey()]), [
            'role' => Role::Placeholder->value,
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Changing role to placeholder is not allowed');
    }

    public function test_invite_placeholder_succeeds_if_data_is_valid(): void
    {
        $data = $this->createUserWithPermission([
            'members:invite-placeholder',
        ]);
        $user = User::factory()->create([
            'is_placeholder' => true,
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($data->organization)->role(Role::Placeholder)->create();
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
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $data->member->getKey()]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_destroy_member_fails_if_member_is_owner(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        $memberToDelete = Member::factory()->forOrganization($data->organization)->role(Role::Owner)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $memberToDelete->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Can not remove owner from organization');
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

    public function test_destroy_endpoint_fails_if_member_is_still_in_use_by_a_time_entry(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $data->member->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'The member is still used by a time entry and can not be deleted.');
        $this->assertDatabaseHas(Member::class, [
            'id' => $data->member->getKey(),
        ]);
    }

    public function test_destroy_endpoint_fails_if_member_is_still_in_use_by_a_project_member(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        ProjectMember::factory()->forProject($project)->forMember($data->member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $data->member->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'The member is still used by a project member and can not be deleted.');
        $this->assertDatabaseHas(Member::class, [
            'id' => $data->member->getKey(),
        ]);
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
        $this->assertDatabaseMissing(Member::class, [
            'id' => $data->member->getKey(),
        ]);
    }

    public function test_invite_placeholder_fails_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $user = User::factory()->create([
            'is_placeholder' => true,
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($data->organization)->create();
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
            'invitations:create',
        ]);
        $otherOrganization = Organization::factory()->create();
        $user = User::factory()->create([
            'is_placeholder' => true,
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($otherOrganization)->create();
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
            'invitations:create',
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
