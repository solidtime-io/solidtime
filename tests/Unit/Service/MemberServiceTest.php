<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\Role;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\MemberService;
use App\Service\UserService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(MemberService::class)]
#[CoversClass(UserService::class)]
class MemberServiceTest extends TestCaseWithDatabase
{
    private MemberService $memberService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->memberService = app(MemberService::class);
    }

    public function test_change_ownership_fails_if_member_is_not_part_of_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $newOwner = Member::factory()->forOrganization($otherOrganization)->create();

        // Act
        $this->expectException(InvalidArgumentException::class);
        $this->memberService->changeOwnership($organization, $newOwner);

        // Assert
        $this->assertDatabaseHas(Organization::class, [
            'id' => $organization->getKey(),
            'user_id' => null,
        ]);
    }

    public function test_change_ownership_changes_ownership_to_new_member(): void
    {
        $organization = Organization::factory()->create();
        $newOwner = User::factory()->create();
        $oldOwner = User::factory()->create();
        $newOwnerMember = Member::factory()->forUser($newOwner)->forOrganization($organization)->role(Role::Admin)->create();
        $oldOwnerMember = Member::factory()->forUser($oldOwner)->forOrganization($organization)->role(Role::Owner)->create();

        // Act
        $this->memberService->changeOwnership($organization, $newOwnerMember);

        // Assert
        $this->assertSame($newOwner->getKey(), $organization->refresh()->user_id);
        $this->assertSame(Role::Owner->value, $newOwnerMember->refresh()->role);
        $this->assertSame(Role::Admin->value, $oldOwnerMember->refresh()->role);
    }

    public function test_make_member_to_placeholder_creates_new_user_based_on_member_and_changes_member_to_placeholder(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();
        $timeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->create();
        $project = Project::factory()->forOrganization($organization)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($member)->create();
        // Note: create other user, organization, member, time entry and project member to check that they are not changed
        $otherUser = User::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $otherMember = Member::factory()->forOrganization($otherOrganization)->forUser($otherUser)->role(Role::Employee)->create();
        $otherTimeEntry = TimeEntry::factory()->forOrganization($otherOrganization)->forMember($otherMember)->create();
        $otherProject = Project::factory()->forOrganization($otherOrganization)->create();
        $otherProjectMember = ProjectMember::factory()->forProject($otherProject)->forMember($otherMember)->create();

        // Act
        $this->memberService->makeMemberToPlaceholder($member);

        // Assert
        $member->refresh();
        $timeEntry->refresh();
        $projectMember->refresh();
        $placeholderUser = $member->user;
        $this->assertTrue($placeholderUser->is_placeholder);
        $this->assertSame(Role::Placeholder->value, $member->role);
        $this->assertSame($organization->getKey(), $member->organization_id);
        $this->assertSame($placeholderUser->getKey(), $projectMember->user_id);
        $this->assertSame($member->getKey(), $projectMember->member_id);
        $this->assertSame($placeholderUser->getKey(), $timeEntry->user_id);
        $this->assertSame($member->getKey(), $timeEntry->member_id);
        $this->assertSame(1, $user->organizations()->count());
        // Note: check that other user did not change
        $otherMember->refresh();
        $otherTimeEntry->refresh();
        $otherProjectMember->refresh();
        $otherUser->refresh();
        $this->assertFalse($otherUser->is_placeholder);
        $this->assertSame(Role::Employee->value, $otherMember->role);
        $this->assertSame($otherOrganization->getKey(), $otherMember->organization_id);
        $this->assertSame($otherUser->getKey(), $otherProjectMember->user_id);
        $this->assertSame($otherMember->getKey(), $otherProjectMember->member_id);
        $this->assertSame($otherUser->getKey(), $otherTimeEntry->user_id);
        $this->assertSame($otherMember->getKey(), $otherTimeEntry->member_id);
        $this->assertSame(1, $otherUser->organizations()->count());
    }

    public function test_make_member_to_placeholder_resets_current_organization_of_user_if_user_is_no_longer_member_to_newly_created_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->forCurrentOrganization($organization)->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $this->memberService->makeMemberToPlaceholder($member);

        // Assert
        $user->refresh();
        $this->assertNotNull($user->current_team_id);
        $this->assertNotSame($organization->id, $user->current_team_id);
    }

    public function test_make_member_to_placeholder_resets_current_organization_of_user_if_user_is_no_longer_member_to_already_existing_other_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->forCurrentOrganization($organization)->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        $otherOrganization = Organization::factory()->create();
        $otherMember = Member::factory()->forOrganization($otherOrganization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $this->memberService->makeMemberToPlaceholder($member);

        // Assert
        $user->refresh();
        $this->assertNotNull($user->current_team_id);
        $this->assertSame($otherOrganization->id, $user->current_team_id);
    }

    public function test_assign_organization_entities_to_different_member_without_any_entries(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();
        $otherUser = User::factory()->create();
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $otherUserMember = Member::factory()->forOrganization($organization)->forUser($otherUser)->create();
        $fromUserMember = Member::factory()->forOrganization($organization)->forUser($fromUser)->create();
        $toUserMember = Member::factory()->forOrganization($organization)->forUser($toUser)->create();
        TimeEntry::factory()->forOrganization($organization)->forMember($otherUserMember)->createMany(3);
        TimeEntry::factory()->forOrganization($organization)->forMember($fromUserMember)->createMany(3);
        ProjectMember::factory()->forProject($project)->forMember($otherUserMember)->create();
        ProjectMember::factory()->forProject($project)->forMember($fromUserMember)->create();

        // Act
        $this->memberService->assignOrganizationEntitiesToDifferentMember($organization, $fromUserMember, $toUserMember);

        // Assert
        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($toUser, 'user')->count());
        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($otherUser, 'user')->count());
        $this->assertSame(0, TimeEntry::query()->whereBelongsTo($fromUser, 'user')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($toUser, 'user')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($otherUser, 'user')->count());
        $this->assertSame(0, ProjectMember::query()->whereBelongsTo($fromUser, 'user')->count());

        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($toUserMember, 'member')->count());
        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($otherUserMember, 'member')->count());
        $this->assertSame(0, TimeEntry::query()->whereBelongsTo($fromUserMember, 'member')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($toUserMember, 'member')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($otherUserMember, 'member')->count());
        $this->assertSame(0, ProjectMember::query()->whereBelongsTo($fromUserMember, 'member')->count());
    }

    public function test_assign_organization_entities_to_different_member_with_entries(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();
        $otherUser = User::factory()->create();
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $otherUserMember = Member::factory()->forOrganization($organization)->forUser($otherUser)->create();
        $fromUserMember = Member::factory()->forOrganization($organization)->forUser($fromUser)->create();
        $toUserMember = Member::factory()->forOrganization($organization)->forUser($toUser)->create();
        TimeEntry::factory()->forOrganization($organization)->forMember($otherUserMember)->createMany(3);
        TimeEntry::factory()->forOrganization($organization)->forMember($fromUserMember)->createMany(3);
        TimeEntry::factory()->forOrganization($organization)->forMember($toUserMember)->createMany(3);
        ProjectMember::factory()->forProject($project)->forMember($otherUserMember)->create([
            'billable_rate' => 1,
        ]);
        ProjectMember::factory()->forProject($project)->forMember($fromUserMember)->create([
            'billable_rate' => 2,
        ]);
        ProjectMember::factory()->forProject($project)->forMember($toUserMember)->create([
            'billable_rate' => 3,
        ]);

        // Act
        $this->memberService->assignOrganizationEntitiesToDifferentMember($organization, $fromUserMember, $toUserMember);

        // Assert
        $this->assertSame(6, TimeEntry::query()->whereBelongsTo($toUser, 'user')->count());
        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($otherUser, 'user')->count());
        $this->assertSame(0, TimeEntry::query()->whereBelongsTo($fromUser, 'user')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($toUser, 'user')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($otherUser, 'user')->count());
        $this->assertSame(0, ProjectMember::query()->whereBelongsTo($fromUser, 'user')->count());

        $this->assertSame(6, TimeEntry::query()->whereBelongsTo($toUserMember, 'member')->count());
        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($otherUserMember, 'member')->count());
        $this->assertSame(0, TimeEntry::query()->whereBelongsTo($fromUserMember, 'member')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($toUserMember, 'member')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($otherUserMember, 'member')->count());
        $this->assertSame(0, ProjectMember::query()->whereBelongsTo($fromUserMember, 'member')->count());

        $this->assertDatabaseCount(ProjectMember::class, 2);
        $this->assertDatabaseHas(ProjectMember::class, [
            'project_id' => $project->id,
            'member_id' => $toUserMember->id,
            'billable_rate' => 3,
        ]);
        $this->assertDatabaseHas(ProjectMember::class, [
            'project_id' => $project->id,
            'member_id' => $otherUserMember->id,
            'billable_rate' => 1,
        ]);
    }
}
