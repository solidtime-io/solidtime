<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\Role;
use App\Events\MemberMadeToPlaceholder;
use App\Events\MemberRemoved;
use App\Http\Controllers\Api\V1\MemberController;
use App\Models\Member;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\BillableRateService;
use Illuminate\Support\Facades\Event;
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

    public function test_index_returns_members_of_organization_sorted_by_name(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:view',
        ]);
        $members = Member::factory()->forOrganization($data->organization)->createMany(4);

        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.members.index', $data->organization->getKey()));

        // Assert
        $response->assertStatus(200);
        // 2 members in $data, 4 members in $members.
        $response->assertJsonCount(6, 'data');

        $memberNames = $members->merge([$data->member, $data->ownerMember])->pluck('user.name')->sort()->values()->all();
        $this->assertEquals($memberNames, $response->json('data.*.name'));
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
        $this->mock(BillableRateService::class, function (MockInterface $mock) use ($data): void {
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

    public function test_update_member_fails_if_user_tries_to_change_the_role_of_a_placeholder(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
        ]);
        $user = User::factory()->placeholder()->create();
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->role(Role::Placeholder)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $member->getKey()]), [
            'role' => Role::Admin->value,
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'changing_role_of_placeholder_is_not_allowed',
            'message' => 'Changing role of placeholder is not allowed',
        ]);
    }

    public function test_merge_into_fails_if_url_member_is_not_part_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:merge-into',
        ]);
        $userSource = User::factory()->placeholder()->create();
        $memberSource = Member::factory()->forUser($userSource)->role(Role::Placeholder)->create();

        $userDestination = User::factory()->create();
        $memberDestination = Member::factory()->forUser($userDestination)->forOrganization($data->organization)->role(Role::Admin)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.merge-into', [$data->organization->getKey(), $memberSource->getKey()]), [
            'member_id' => $memberDestination->getKey(),
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_merge_into_returns_validation_error_if_member_in_body_does_not_belong_to_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:merge-into',
        ]);
        $userSource = User::factory()->placeholder()->create();
        $memberSource = Member::factory()->forUser($userSource)->forOrganization($data->organization)->role(Role::Placeholder)->create();

        $userDestination = User::factory()->create();
        $memberDestination = Member::factory()->forUser($userDestination)->role(Role::Admin)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.merge-into', [$data->organization->getKey(), $memberSource->getKey()]), [
            'member_id' => $memberDestination->getKey(),
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertExactJson([
            'errors' => [
                'member_id' => [
                    'The resource does not exist.',
                ],
            ],
            'message' => 'The resource does not exist.',
        ]);
    }

    public function test_merge_into_fails_if_from_member_is_not_a_placeholder(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:merge-into',
        ]);
        $userSource = User::factory()->placeholder()->create();
        $memberSource = Member::factory()->forUser($userSource)->forOrganization($data->organization)->role(Role::Admin)->create();

        $userDestination = User::factory()->create();
        $memberDestination = Member::factory()->forUser($userDestination)->forOrganization($data->organization)->role(Role::Admin)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.merge-into', [$data->organization->getKey(), $memberSource->getKey()]), [
            'member_id' => $memberDestination->getKey(),
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'only_placeholders_can_be_merged_into_another_member',
            'message' => 'Only placeholders can be merged into another member',
        ]);
    }

    public function test_merge_into_fails_if_user_has_no_permission_to_merge_members(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        $userSource = User::factory()->placeholder()->create();
        $memberSource = Member::factory()->forUser($userSource)->forOrganization($data->organization)->role(Role::Placeholder)->create();

        $userDestination = User::factory()->create();
        $memberDestination = Member::factory()->forUser($userDestination)->forOrganization($data->organization)->role(Role::Admin)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.merge-into', [$data->organization->getKey(), $memberSource->getKey()]), [
            'member_id' => $memberDestination->getKey(),
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_merge_into_assigns_resources_of_source_member_to_destination_member_and_deletes_member(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:merge-into',
        ]);
        $userSource = User::factory()->placeholder()->create();
        $memberSource = Member::factory()->forUser($userSource)->forOrganization($data->organization)->role(Role::Placeholder)->create();
        TimeEntry::factory()->forMember($memberSource)->createMany(3);
        $project = Project::factory()->forOrganization($data->organization)->create();
        ProjectMember::factory()->forMember($memberSource)->forProject($project)->create();

        $userDestination = User::factory()->create();
        $memberDestination = Member::factory()->forUser($userDestination)->forOrganization($data->organization)->role(Role::Admin)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.merge-into', [$data->organization->getKey(), $memberSource->getKey()]), [
            'member_id' => $memberDestination->getKey(),
        ]);

        // Assert
        $response->assertStatus(204);
        $this->assertSame('', $response->getContent());
        $this->assertDatabaseMissing(Member::class, [
            'id' => $memberSource->getKey(),
        ]);
        $this->assertDatabaseMissing(User::class, [
            'id' => $userSource->getKey(),
        ]);
        $memberDestination->refresh();
        $this->assertCount(3, $memberDestination->timeEntries);
        $this->assertCount(1, $memberDestination->projectMembers);
        $this->assertDatabaseHas(ProjectMember::class, [
            'project_id' => $project->getKey(),
            'member_id' => $memberDestination->getKey(),
            'user_id' => $userDestination->getKey(),
        ]);
    }

    public function test_merge_into_assigns_resources_of_source_member_to_destination_member_and_deletes_member_with_existing_destination_resources(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:merge-into',
        ]);
        $userSource = User::factory()->placeholder()->create();
        $memberSource = Member::factory()->forUser($userSource)->forOrganization($data->organization)->role(Role::Placeholder)->create();
        TimeEntry::factory()->forMember($memberSource)->createMany(3);
        $project = Project::factory()->forOrganization($data->organization)->create();
        ProjectMember::factory()->forMember($memberSource)->forProject($project)->create([
            'billable_rate' => 32100,
        ]);

        $userDestination = User::factory()->create();
        $memberDestination = Member::factory()->forUser($userDestination)->forOrganization($data->organization)->role(Role::Admin)->create();
        ProjectMember::factory()->forMember($memberDestination)->forProject($project)->create([
            'billable_rate' => 12300,
        ]);
        TimeEntry::factory()->forMember($memberDestination)->createMany(3);
        Passport::actingAs($data->user);

        // Act
        $response = $this->withoutExceptionHandling()->postJson(route('api.v1.members.merge-into', [$data->organization->getKey(), $memberSource->getKey()]), [
            'member_id' => $memberDestination->getKey(),
        ]);

        // Assert
        $response->assertStatus(204);
        $this->assertSame('', $response->getContent());
        $this->assertDatabaseMissing(Member::class, [
            'id' => $memberSource->getKey(),
        ]);
        $this->assertDatabaseMissing(User::class, [
            'id' => $userSource->getKey(),
        ]);
        $memberDestination->refresh();
        $this->assertCount(6, $memberDestination->timeEntries);
        $this->assertCount(1, $memberDestination->projectMembers);
        $this->assertDatabaseHas(ProjectMember::class, [
            'project_id' => $project->getKey(),
            'billable_rate' => 12300,
            'member_id' => $memberDestination->getKey(),
            'user_id' => $userDestination->getKey(),
        ]);
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
        // Arrange
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

    public function test_invite_placeholder_fails_if_the_placeholder_has_a_invalid_email_from_an_import(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:invite-placeholder',
        ]);
        $user = User::factory()->create([
            'is_placeholder' => true,
            'email' => 'some.user@solidtime-import.test',
        ]);
        $member = Member::factory()
            ->forUser($user)
            ->forOrganization($data->organization)
            ->role(Role::Placeholder)
            ->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.invite-placeholder', [
            'organization' => $data->organization->getKey(),
            'member' => $member->getKey(),
        ]));

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'this_placeholder_can_not_be_invited_use_the_merge_tool_instead_api_exception',
            'message' => 'This placeholder can not be invited use the merge tool instead',
        ]);
    }

    public function test_destroy_member_fails_if_user_has_no_permission_to_delete_members(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);
        Event::fake([
            MemberRemoved::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $data->member->getKey()]));

        // Assert
        $response->assertStatus(403);
        Event::assertNotDispatched(MemberRemoved::class);
    }

    public function test_destroy_member_fails_if_member_is_owner(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        $memberToDelete = Member::factory()->forOrganization($data->organization)->role(Role::Owner)->create();
        Passport::actingAs($data->user);
        Event::fake([
            MemberRemoved::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $memberToDelete->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Can not remove owner from organization');
        Event::assertNotDispatched(MemberRemoved::class);
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
        Event::fake([
            MemberRemoved::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $otherData->member->getKey()]));

        // Assert
        $response->assertStatus(403);
        Event::assertNotDispatched(MemberRemoved::class);
    }

    public function test_destroy_endpoint_fails_if_member_is_still_in_use_by_a_time_entry(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);
        Event::fake([
            MemberRemoved::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $data->member->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'The member is still used by a time entry and can not be deleted.');
        $this->assertDatabaseHas(Member::class, [
            'id' => $data->member->getKey(),
        ]);
        Event::assertNotDispatched(MemberRemoved::class);
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
        Event::fake([
            MemberRemoved::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $data->member->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'The member is still used by a project member and can not be deleted.');
        $this->assertDatabaseHas(Member::class, [
            'id' => $data->member->getKey(),
        ]);
        Event::assertNotDispatched(MemberRemoved::class);
    }

    public function test_destroy_endpoint_also_deletes_user_if_member_is_placeholder(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        $user = User::factory()->placeholder()->create();
        $member = Member::factory()->forUser($user)->forOrganization($data->organization)->role(Role::Placeholder)->create();
        Passport::actingAs($data->user);
        Event::fake([
            MemberRemoved::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $member->getKey()]));

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing(Member::class, [
            'id' => $member->getKey(),
        ]);
        $this->assertDatabaseMissing(User::class, [
            'id' => $user->getKey(),
        ]);
        Event::assertDispatched(function (MemberRemoved $event) use ($data, $member): bool {
            return $event->organization->is($data->organization) &&
                $event->member->is($member);
        }, 1);
    }

    public function test_destroy_endpoint_sets_current_organization_to_organization_the_user_is_still_member_of(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        $user = $data->user;
        $otherOrganization = Organization::factory()->create();
        $otherMember = Member::factory()->forOrganization($otherOrganization)->forUser($user)->role(Role::Employee)->create();
        Passport::actingAs($user);
        Event::fake([
            MemberRemoved::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $data->member->getKey()]));

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing(Member::class, [
            'id' => $data->member->getKey(),
        ]);
        $user->refresh();
        $this->assertSame($otherOrganization->getKey(), $user->currentOrganization->getKey());
        Event::assertDispatched(function (MemberRemoved $event) use ($data): bool {
            return $event->organization->is($data->organization) &&
                $event->member->is($data->member);
        }, 1);
    }

    public function test_destroy_endpoint_creates_new_organization_and_sets_the_current_organization_to_it_if_user_is_not_member_of_any_other_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        $organization = $data->organization;
        $user = $data->user;
        Passport::actingAs($user);
        Event::fake([
            MemberRemoved::class,
        ]);
        $this->assertDatabaseCount(Organization::class, 1);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $data->member->getKey()]));

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseCount(Organization::class, 2);
        $newOrganization = Organization::where('id', '!=', $organization->getKey())->first();
        $this->assertNotNull($newOrganization);
        $this->assertDatabaseMissing(Member::class, [
            'id' => $data->member->getKey(),
        ]);
        $this->assertDatabaseHas(Member::class, [
            'organization_id' => $newOrganization->getKey(),
            'user_id' => $user->getKey(),
        ]);
        $user->refresh();
        $this->assertNotNull($user->currentOrganization);
        Event::assertDispatched(function (MemberRemoved $event) use ($data): bool {
            return $event->organization->is($data->organization) &&
                $event->member->is($data->member);
        }, 1);
    }

    public function test_destroy_endpoint_succeeds_if_member_is_still_in_use_by_a_project_member_and_delete_related_is_active(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        $otherMember = Member::factory()->forOrganization($data->organization)->role(Role::Employee)->create();
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($data->member)->create();
        $otherProjectMember = ProjectMember::factory()->forProject($project)->forMember($otherMember)->create();
        Passport::actingAs($data->user);
        Event::fake([
            MemberRemoved::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [
            'organization' => $data->organization->getKey(),
            'member' => $data->member->getKey(),
            'delete_related' => 'true',
        ]));

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing(Member::class, [
            'id' => $data->member->getKey(),
        ]);
        $this->assertDatabaseHas(ProjectMember::class, [
            'id' => $otherProjectMember->getKey(),
            'member_id' => $otherMember->getKey(),
            'user_id' => $otherMember->user_id,
        ]);
        $this->assertDatabaseMissing(ProjectMember::class, [
            'id' => $projectMember->getKey(),
        ]);
        Event::assertDispatched(function (MemberRemoved $event) use ($data): bool {
            return $event->organization->is($data->organization) &&
                $event->member->is($data->member);
        }, 1);
    }

    public function test_destroy_endpoint_succeeds_if_member_is_still_in_use_by_a_time_entry_and_delete_related_is_active(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        $otherMember = Member::factory()->forOrganization($data->organization)->role(Role::Employee)->create();
        $timeEntry = TimeEntry::factory()->forMember($data->member)->forOrganization($data->organization)->create();
        $otherTimeEntry = TimeEntry::factory()->forMember($otherMember)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);
        Event::fake([
            MemberRemoved::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [
            'organization' => $data->organization->getKey(),
            'member' => $data->member->getKey(),
            'delete_related' => 'true',
        ]));

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing(Member::class, [
            'id' => $data->member->getKey(),
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $otherTimeEntry->getKey(),
        ]);
        $this->assertDatabaseMissing(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
        ]);
        Event::assertDispatched(function (MemberRemoved $event) use ($data): bool {
            return $event->organization->is($data->organization) &&
                $event->member->is($data->member);
        }, 1);
    }

    public function test_destroy_member_succeeds_if_data_is_valid(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:delete',
        ]);
        Passport::actingAs($data->user);
        Event::fake([
            MemberRemoved::class,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.members.destroy', [$data->organization->getKey(), $data->member->getKey()]));

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing(Member::class, [
            'id' => $data->member->getKey(),
        ]);
        Event::assertDispatched(function (MemberRemoved $event) use ($data): bool {
            return $event->organization->is($data->organization) &&
                $event->member->is($data->member);
        }, 1);
    }

    public function test_make_placeholder_fails_if_user_has_no_permission(): void
    {
        // Arrange
        Event::fake([
            MemberMadeToPlaceholder::class,
        ]);
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.make-placeholder', [
            'organization' => $data->organization->getKey(),
            'member' => $data->member->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
        Event::assertNotDispatched(MemberMadeToPlaceholder::class);
    }

    public function test_make_placeholder_fails_if_user_is_already_a_placeholder(): void
    {
        // Arrange
        Event::fake([
            MemberMadeToPlaceholder::class,
        ]);
        $data = $this->createUserWithPermission([
            'members:make-placeholder',
        ]);
        $user = User::factory()->placeholder()->create();
        $member = Member::factory()->forUser($user)->forOrganization($data->organization)->role(Role::Placeholder)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.make-placeholder', [
            'organization' => $data->organization->getKey(),
            'member' => $member->getKey(),
        ]));

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'changing_role_of_placeholder_is_not_allowed',
            'message' => 'Changing role of placeholder is not allowed',
        ]);
    }

    public function test_make_placeholder_fails_if_member_is_owner(): void
    {
        // Arrange
        Event::fake([
            MemberMadeToPlaceholder::class,
        ]);
        $data = $this->createUserWithPermission([
            'members:make-placeholder',
        ]);
        $member = Member::factory()->forOrganization($data->organization)->role(Role::Owner)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.make-placeholder', [
            'organization' => $data->organization->getKey(),
            'member' => $member->getKey(),
        ]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Can not remove owner from organization');
        Event::assertNotDispatched(MemberMadeToPlaceholder::class);
    }

    public function test_make_placeholder_fails_if_member_is_not_part_of_org(): void
    {
        // Arrange
        Event::fake([
            MemberMadeToPlaceholder::class,
        ]);
        $data = $this->createUserWithPermission([
            'members:make-placeholder',
        ]);
        $otherData = $this->createUserWithPermission([
            'members:make-placeholder',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.make-placeholder', [
            'organization' => $data->organization->getKey(),
            'member' => $otherData->member->getKey(),
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_make_placeholder_creates_placeholder_and_attaches_resources_to_the_new_user(): void
    {
        // Arrange
        Event::fake([
            MemberMadeToPlaceholder::class,
        ]);
        $data = $this->createUserWithPermission([
            'members:make-placeholder',
        ]);
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->role(Role::Admin)->create();
        $timeEntry = TimeEntry::factory()->forMember($member)->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.make-placeholder', [
            'organization' => $data->organization->getKey(),
            'member' => $member->getKey(),
        ]));

        // Assert
        $response->assertStatus(204);
        $member->refresh();
        $this->assertSame(Role::Placeholder->value, $member->role);
        $this->assertTrue($member->user->is_placeholder);
        $this->assertCount(1, $user->organizations);
        $this->assertCount(1, $member->user->organizations);
        $this->assertNotEquals($user->getKey(), $member->user->getKey());
        $timeEntry->refresh();
        $this->assertSame($member->user_id, $timeEntry->user_id);
        $projectMember->refresh();
        $this->assertSame($member->user_id, $projectMember->user_id);
        Event::assertDispatched(function (MemberMadeToPlaceholder $event) use ($data, $member): bool {
            return $event->organization->is($data->organization) &&
                $event->member->is($member);
        }, 1);
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

    public function test_invite_placeholder_fails_if_there_is_already_an_invitation_with_the_same_email(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:invite-placeholder',
            'invitations:create',
        ]);
        $placeholder = User::factory()->placeholder()->create([
            'email' => 'user@mail.test',
        ]);
        $placeholderMember = Member::factory()->forUser($placeholder)->forOrganization($data->organization)->role(Role::Placeholder)->create();
        OrganizationInvitation::factory()->forOrganization($data->organization)->create([
            'email' => $placeholder->email,
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.members.invite-placeholder', [
            'organization' => $data->organization->id,
            'member' => $placeholderMember->id,
        ]));

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'invitation_for_the_email_already_exists',
            'message' => 'The email has already been invited to the organization. Please wait for the user to accept the invitation or resend the invitation email.',
        ]);
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
