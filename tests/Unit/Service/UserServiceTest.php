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
use App\Service\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

#[CoversClass(UserService::class)]
#[UsesClass(UserService::class)]
class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = app(UserService::class);
    }

    public function test_assign_organization_entities_to_different_user(): void
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
        $this->userService->assignOrganizationEntitiesToDifferentUser($organization, $fromUser, $toUser);

        // Assert
        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($toUser, 'user')->count());
        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($otherUser, 'user')->count());
        $this->assertSame(0, TimeEntry::query()->whereBelongsTo($fromUser, 'user')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($toUser, 'user')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($otherUser, 'user')->count());
        $this->assertSame(0, ProjectMember::query()->whereBelongsTo($fromUser, 'user')->count());
    }

    public function test_assign_organization_entities_to_different_user_fails_if_new_user_is_not_member_of_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        $fromUserMember = Member::factory()->forOrganization($organization)->forUser($fromUser)->create();

        // Act
        try {
            $this->userService->assignOrganizationEntitiesToDifferentUser($organization, $fromUser, $toUser);
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('User is not a member of the organization', $e->getMessage());
        }
    }

    public function test_change_ownership_changes_ownership_of_organization_to_new_user(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $newOwner = User::factory()->create();
        $oldOwner = User::factory()->create();
        $organization->users()->attach($oldOwner->getKey(), [
            'role' => Role::Owner->value,
        ]);
        $organization->users()->attach($newOwner->getKey(), [
            'role' => Role::Admin->value,
        ]);

        // Act
        $this->userService->changeOwnership($organization, $newOwner);

        // Assert
        $this->assertSame($newOwner->getKey(), $organization->refresh()->user_id);
        $this->assertSame(Role::Owner->value, Member::whereBelongsTo($newOwner)->whereBelongsTo($organization)->firstOrFail()->role);
        $this->assertSame(Role::Admin->value, Member::whereBelongsTo($oldOwner)->whereBelongsTo($organization)->firstOrFail()->role);
    }

    public function test_change_ownership_fails_if_new_user_is_not_member_of_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $newOwner = User::factory()->create();
        $oldOwner = User::factory()->create();
        $organization->users()->attach($oldOwner->getKey(), [
            'role' => Role::Owner->value,
        ]);

        // Act
        try {
            $this->userService->changeOwnership($organization, $newOwner);
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('User is not a member of the organization', $e->getMessage());
        }
    }

    public function test_make_sure_user_has_current_organization_sets_current_organization_for_user_if_null(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        Member::factory()->forUser($user)->forOrganization($organization)->create();
        $user->current_team_id = null;
        $user->save();

        // Act
        $this->userService->makeSureUserHasCurrentOrganization($user);

        // Assert
        $this->assertSame($organization->getKey(), $user->refresh()->currentOrganization->getKey());
    }

    public function make_sure_user_has_at_least_one_organization_creates_organization_for_user_if_there_are_not_member_of_one(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        // Act
        $this->userService->makeSureUserHasAtLeastOneOrganization($user);

        // Assert
        $user->refresh();
        $this->assertSame(1, $user->organizations()->count());
        $newOrganization = $user->organizations()->first();
        $this->assertNotSame($organization->getKey(), $newOrganization->getKey());
        $this->assertSame($user->name."'s Organization", $newOrganization->name);
        $this->assertTrue($newOrganization->personal_team);
        $this->assertSame($user->getKey(), $newOrganization->user_id);
        $newMember = Member::whereBelongsTo($user)->whereBelongsTo($newOrganization)->firstOrFail();
        $this->assertSame(Role::Owner->value, $newMember->role);
        $this->assertSame($newOrganization->getKey(), $user->currentOrganization->getKey());
    }
}
