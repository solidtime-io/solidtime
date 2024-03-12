<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\Organization;
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
        $otherUser = User::factory()->create();
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        TimeEntry::factory()->forOrganization($organization)->forUser($otherUser)->createMany(3);
        TimeEntry::factory()->forOrganization($organization)->forUser($fromUser)->createMany(3);

        // Act
        $userService = app(UserService::class);
        $userService->assignOrganizationEntitiesToDifferentUser($organization, $fromUser, $toUser);

        // Assert
        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($toUser, 'user')->count());
        $this->assertSame(3, TimeEntry::query()->whereBelongsTo($otherUser, 'user')->count());
        $this->assertSame(0, TimeEntry::query()->whereBelongsTo($fromUser, 'user')->count());
    }
}
