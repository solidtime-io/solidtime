<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Organization;
use App\Models\TimeEntry;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Illuminate\Support\Facades\Config;

class UserModelTest extends ModelTestAbstract
{
    public function test_normal_user_can_not_access_admin_panel(): void
    {
        // Arrange
        Config::set('auth.super_admins', ['some@email.test', 'other@email.test']);
        $user = User::factory()->create();
        $panelProvider = new AdminPanelProvider(app());
        $mainPanel = $panelProvider->panel(Panel::make());

        // Act
        $canAccess = $user->canAccessPanel($mainPanel);

        // Assert
        $this->assertFalse($canAccess);
    }

    public function test_user_in_super_admin_config_can_access_admin_panel(): void
    {
        // Arrange
        Config::set('auth.super_admins', ['some@email.test', 'other@email.test']);
        $user = User::factory()->create([
            'email' => 'some@email.test',
        ]);
        $panelProvider = new AdminPanelProvider(app());
        $mainPanel = $panelProvider->panel(Panel::make());

        // Act
        $canAccess = $user->canAccessPanel($mainPanel);

        // Assert
        $this->assertTrue($canAccess);
    }

    public function test_scope_belongs_to_organization_returns_only_users_of_organization_including_owners(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $organization = Organization::factory()->withOwner($owner)->create();
        $user = User::factory()->create();
        $user->organizations()->attach($organization, [
            'role' => 'employee',
        ]);
        $otherOrganization = Organization::factory()->create();
        $otherUser = User::factory()->create();
        $otherUser->organizations()->attach($otherOrganization, [
            'role' => 'employee',
        ]);

        // Act
        $users = User::query()
            ->belongsToOrganization($organization)
            ->get();

        // Assert
        $this->assertCount(2, $users);
        $userIds = $users->pluck('id')->toArray();
        $this->assertContains($user->getKey(), $userIds);
        $this->assertContains($owner->getKey(), $userIds);
    }

    public function test_it_has_many_time_entries(): void
    {
        // Arrange
        $user = User::factory()->create();
        $timeEntries = TimeEntry::factory()->forUser($user)->createMany(3);

        // Act
        $user->refresh();
        $timeEntriesRel = $user->timeEntries;

        // Assert
        $this->assertNotNull($timeEntriesRel);
        $this->assertCount(3, $timeEntriesRel);
        $this->assertTrue($timeEntriesRel->first()->is($timeEntries->first()));
    }
}
