<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\Role;
use App\Enums\TimeEntryType;
use App\Models\Goal;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\Dto\GoalFiltersDto;
use App\Service\MemberService;
use App\Service\UserService;
use Illuminate\Support\Facades\Hash;
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

    public function test_make_member_to_placeholder_does_not_copy_the_credentials_and_account_state_of_the_user(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => Hash::make('secret-password'),
            'remember_token' => 'remember-me-token',
            'two_factor_secret' => 'two-factor-secret',
            'two_factor_recovery_codes' => 'two-factor-recovery-codes',
            'two_factor_confirmed_at' => '2026-09-16 10:00:00',
            'email_verified_at' => '2026-09-16 09:00:00',
            'pending_email' => 'pending@example.com',
            'profile_photo_path' => 'profile-photos/photo.png',
        ]);
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();

        // Act
        $this->memberService->makeMemberToPlaceholder($member);

        // Assert
        $member->refresh();
        $placeholderUser = $member->user;
        $this->assertTrue($placeholderUser->is_placeholder);
        $this->assertSame($user->email, $placeholderUser->email);
        $this->assertNull($placeholderUser->password);
        $this->assertNull($placeholderUser->remember_token);
        $this->assertNull($placeholderUser->two_factor_secret);
        $this->assertNull($placeholderUser->two_factor_recovery_codes);
        $this->assertNull($placeholderUser->two_factor_confirmed_at);
        $this->assertNull($placeholderUser->email_verified_at);
        $this->assertNull($placeholderUser->pending_email);
        $this->assertNull($placeholderUser->current_team_id);
        $this->assertNull($placeholderUser->profile_photo_path);
        // the user the placeholder was created from keeps their own credentials and state
        $user->refresh();
        $this->assertTrue(Hash::check('secret-password', (string) $user->password));
        $this->assertSame('two-factor-secret', $user->two_factor_secret);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('pending@example.com', $user->pending_email);
        $this->assertSame('profile-photos/photo.png', $user->profile_photo_path);
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

    public function test_assign_organization_entities_to_different_member_moves_all_goals_of_the_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $fromMember = Member::factory()->forOrganization($organization)->create();
        $toMember = Member::factory()->forOrganization($organization)->create();
        $otherMember = Member::factory()->forOrganization($organization)->create();
        $personalGoal = Goal::factory()->forMember($fromMember)->create();
        $organizationGoalForFromMember = Goal::factory()->organizationGoalForMember($fromMember)->create();
        $organizationGoalForOtherMember = Goal::factory()->organizationGoalForMember($otherMember)->create();
        $goalForEveryMember = Goal::factory()->forOrganization($organization)->forEveryMember()->create();
        $personalGoalOfOtherMember = Goal::factory()->forMember($otherMember)->create();
        $goalOfOtherOrganization = Goal::factory()->forOrganization($otherOrganization)->create();

        // Act
        $this->memberService->assignOrganizationEntitiesToDifferentMember($organization, $fromMember, $toMember);

        // Assert
        $this->assertSame($toMember->getKey(), $personalGoal->refresh()->member_id);
        $this->assertSame($toMember->getKey(), $organizationGoalForFromMember->refresh()->member_id);
        $this->assertSame($otherMember->getKey(), $organizationGoalForOtherMember->refresh()->member_id);
        $this->assertNull($goalForEveryMember->refresh()->member_id);
        $this->assertSame($otherMember->getKey(), $personalGoalOfOtherMember->refresh()->member_id);
        $this->assertDatabaseHas(Goal::class, ['id' => $goalOfOtherOrganization->getKey()]);
        $this->assertSame(0, $fromMember->goals()->count());
    }

    public function test_assign_organization_entities_to_different_member_replaces_the_member_in_goal_member_filters(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $fromMember = Member::factory()->forOrganization($organization)->create();
        $toMember = Member::factory()->forOrganization($organization)->create();
        $otherMember = Member::factory()->forOrganization($organization)->create();
        $goalWithFromMember = Goal::factory()->forOrganization($organization)->forEveryMember()
            ->filters($this->memberFilter([$fromMember->getKey(), $otherMember->getKey()]))->create();
        $goalWithBothMembers = Goal::factory()->forOrganization($organization)->forEveryMember()
            ->filters($this->memberFilter([$toMember->getKey(), $fromMember->getKey()]))->create();
        $goalWithoutFromMember = Goal::factory()->forOrganization($organization)->forEveryMember()
            ->filters($this->memberFilter([$otherMember->getKey()]))->create();
        $goalWithoutMemberFilter = Goal::factory()->forOrganization($organization)->forEveryMember()->create();
        // Can not happen through the API, but a filter of another organization must stay untouched
        $goalOfOtherOrganization = Goal::factory()->forOrganization($otherOrganization)->forEveryMember()
            ->filters($this->memberFilter([$fromMember->getKey()]))->create();

        // Act
        $this->memberService->assignOrganizationEntitiesToDifferentMember($organization, $fromMember, $toMember);

        // Assert
        $this->assertSame([$toMember->getKey(), $otherMember->getKey()], $goalWithFromMember->refresh()->filters->memberIds?->all());
        // No duplicate if $toMember was already in the filter
        $this->assertSame([$toMember->getKey()], $goalWithBothMembers->refresh()->filters->memberIds?->all());
        $this->assertSame([$otherMember->getKey()], $goalWithoutFromMember->refresh()->filters->memberIds?->all());
        $this->assertNull($goalWithoutMemberFilter->refresh()->filters->memberIds);
        $this->assertSame([$fromMember->getKey()], $goalOfOtherOrganization->refresh()->filters->memberIds?->all());
    }

    /**
     * @param  array<string>  $memberIds
     */
    private function memberFilter(array $memberIds): GoalFiltersDto
    {
        $filters = new GoalFiltersDto;
        $filters->timeEntryType = TimeEntryType::Work;
        $filters->setMemberIds($memberIds);

        return $filters;
    }

    public function test_make_member_to_placeholder_keeps_all_goals_of_the_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();
        $otherMember = Member::factory()->forOrganization($organization)->create();
        $personalGoal = Goal::factory()->forMember($member)->create();
        $organizationGoal = Goal::factory()->organizationGoalForMember($member)->create();
        $goalOfOtherMember = Goal::factory()->forMember($otherMember)->create();

        // Act
        $this->memberService->makeMemberToPlaceholder($member);

        // Assert
        $member->refresh();
        $this->assertTrue($member->user->is_placeholder);
        $this->assertDatabaseHas(Goal::class, ['id' => $personalGoal->getKey(), 'member_id' => $member->getKey()]);
        $this->assertDatabaseHas(Goal::class, ['id' => $organizationGoal->getKey(), 'member_id' => $member->getKey()]);
        $this->assertDatabaseHas(Goal::class, ['id' => $goalOfOtherMember->getKey()]);
    }

    public function test_remove_member_deletes_the_goals_of_the_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();
        $otherMember = Member::factory()->forOrganization($organization)->create();
        $personalGoal = Goal::factory()->forMember($member)->create();
        $organizationGoal = Goal::factory()->organizationGoalForMember($member)->create();
        $goalForEveryMember = Goal::factory()->forOrganization($organization)->forEveryMember()->create();
        $goalOfOtherMember = Goal::factory()->forMember($otherMember)->create();

        // Act
        $this->memberService->removeMember($member, $organization);

        // Assert
        $this->assertDatabaseMissing(Member::class, ['id' => $member->getKey()]);
        $this->assertDatabaseMissing(Goal::class, ['id' => $personalGoal->getKey()]);
        $this->assertDatabaseMissing(Goal::class, ['id' => $organizationGoal->getKey()]);
        $this->assertDatabaseHas(Goal::class, ['id' => $goalForEveryMember->getKey()]);
        $this->assertDatabaseHas(Goal::class, ['id' => $goalOfOtherMember->getKey()]);
    }

    public function test_add_member_moves_all_goals_of_placeholder_with_same_email_to_new_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Employee)->create();
        $personalGoal = Goal::factory()->forMember($member)->create();
        $organizationGoal = Goal::factory()->organizationGoalForMember($member)->create();
        $this->memberService->makeMemberToPlaceholder($member);

        // Act
        $newMember = $this->memberService->addMember($user, $organization, Role::Employee);

        // Assert
        $this->assertDatabaseMissing(Member::class, ['id' => $member->getKey()]);
        $this->assertDatabaseHas(Goal::class, ['id' => $personalGoal->getKey(), 'member_id' => $newMember->getKey()]);
        $this->assertDatabaseHas(Goal::class, ['id' => $organizationGoal->getKey(), 'member_id' => $newMember->getKey()]);
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
