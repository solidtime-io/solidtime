<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\Role;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_organization_entities_to_different_user(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();
        $otherUser = User::factory()->create();
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        TimeEntry::factory()->forOrganization($organization)->forUser($otherUser)->createMany(3);
        TimeEntry::factory()->forOrganization($organization)->forUser($fromUser)->createMany(3);
        ProjectMember::factory()->forProject($project)->forUser($otherUser)->create();
        ProjectMember::factory()->forProject($project)->forUser($fromUser)->create();

        // Act
        /** @var UserService $userService */
        $userService = app(UserService::class);
        $userService->assignOrganizationEntitiesToDifferentUser($organization, $fromUser, $toUser);

        // Assert
        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($toUser, 'user')->count());
        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($otherUser, 'user')->count());
        $this->assertSame(0, TimeEntry::query()->whereBelongsTo($fromUser, 'user')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($toUser, 'user')->count());
        $this->assertSame(1, ProjectMember::query()->whereBelongsTo($otherUser, 'user')->count());
        $this->assertSame(0, ProjectMember::query()->whereBelongsTo($fromUser, 'user')->count());
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
        /** @var UserService $userService */
        $userService = app(UserService::class);
        $userService->changeOwnership($organization, $newOwner);

        // Assert
        $this->assertSame($newOwner->id, $organization->refresh()->user_id);
        $this->assertSame(Role::Owner->value, Membership::whereBelongsTo($newOwner)->whereBelongsTo($organization)->firstOrFail()->role);
        $this->assertSame(Role::Admin->value, Membership::whereBelongsTo($oldOwner)->whereBelongsTo($organization)->firstOrFail()->role);
    }
}
