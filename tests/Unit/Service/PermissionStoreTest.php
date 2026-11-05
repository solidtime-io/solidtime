<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use App\Service\PermissionStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(PermissionStore::class)]
class PermissionStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_method_returns_false_when_user_is_not_authenticated(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => Role::Employee->value]);
        $permissionStore = new PermissionStore;

        // Act
        $result = $permissionStore->has($organization, 'permission');

        // Assert
        $this->assertFalse($result);
    }

    public function test_has_method_returns_false_when_user_does_not_belong_to_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $permissionStore = new PermissionStore;
        $this->actingAs($user);

        // Act
        $result = $permissionStore->has($organization, 'permission');

        // Assert
        $this->assertFalse($result);
    }

    public function test_has_method_returns_false_when_user_does_not_have_permission(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => Role::Employee->value]);
        $permissionStore = new PermissionStore;
        $this->actingAs($user);

        // Act
        $result = $permissionStore->has($organization, 'permission');

        // Assert
        $this->assertFalse($result);
    }

    public function test_has_method_returns_true_when_user_has_permission(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => Role::Employee->value]);
        $permissionStore = new PermissionStore;
        $this->actingAs($user);

        // Act
        $result = $permissionStore->has($organization, 'time-entries:view:own');

        // Assert
        $this->assertTrue($result);
    }

    public function test_get_permissions_method_returns_empty_array_when_user_is_not_authenticated(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => Role::Employee->value]);
        $permissionStore = new PermissionStore;

        // Act
        $result = $permissionStore->getPermissions($organization);

        // Assert
        $this->assertEmpty($result);
    }

    public function test_get_permissions_method_returns_empty_array_when_user_does_not_belong_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $permissionStore = new PermissionStore;
        $this->actingAs($user);

        // Act
        $result = $permissionStore->getPermissions($organization);

        // Assert
        $this->assertEmpty($result);
    }

    public function test_get_permissions_method_returns_permissions_when_user_belongs_to_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => Role::Employee->value]);
        $permissionStore = new PermissionStore;
        $this->actingAs($user);

        // Act
        $result = $permissionStore->getPermissions($organization);

        // Assert
        $this->assertSame(PermissionStore::permissionsForRole(Role::Employee->value), $result);
    }

    public function test_employee_does_not_have_task_permissions_by_default(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'employees_can_manage_tasks' => false,
        ]);
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => Role::Employee->value]);
        $permissionStore = new PermissionStore;
        $this->actingAs($user);

        // Act & Assert
        $this->assertFalse($permissionStore->has($organization, 'tasks:create'));
        $this->assertFalse($permissionStore->has($organization, 'tasks:update'));
        $this->assertFalse($permissionStore->has($organization, 'tasks:delete'));
        $this->assertFalse($permissionStore->has($organization, 'tasks:create:all'));
        $this->assertFalse($permissionStore->has($organization, 'tasks:update:all'));
        $this->assertFalse($permissionStore->has($organization, 'tasks:delete:all'));
    }

    public function test_employee_has_task_permissions_when_organization_allows_it(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'employees_can_manage_tasks' => true,
        ]);
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => Role::Employee->value]);
        $permissionStore = new PermissionStore;
        $this->actingAs($user);

        // Act & Assert
        $this->assertTrue($permissionStore->has($organization, 'tasks:create'));
        $this->assertTrue($permissionStore->has($organization, 'tasks:update'));
        $this->assertTrue($permissionStore->has($organization, 'tasks:delete'));
        // Should NOT have the :all permissions
        $this->assertFalse($permissionStore->has($organization, 'tasks:create:all'));
        $this->assertFalse($permissionStore->has($organization, 'tasks:update:all'));
        $this->assertFalse($permissionStore->has($organization, 'tasks:delete:all'));
    }

    public function test_non_employee_roles_are_not_affected_by_employees_can_manage_tasks_setting(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'employees_can_manage_tasks' => false,
        ]);
        $admin = User::factory()->create();
        $organization->users()->attach($admin, ['role' => Role::Admin->value]);
        $permissionStore = new PermissionStore;
        $this->actingAs($admin);

        // Act & Assert - Admin should have task permissions regardless of the setting
        $this->assertTrue($permissionStore->has($organization, 'tasks:create'));
        $this->assertTrue($permissionStore->has($organization, 'tasks:update'));
        $this->assertTrue($permissionStore->has($organization, 'tasks:delete'));
        $this->assertTrue($permissionStore->has($organization, 'tasks:create:all'));
        $this->assertTrue($permissionStore->has($organization, 'tasks:update:all'));
        $this->assertTrue($permissionStore->has($organization, 'tasks:delete:all'));
    }

    public function test_get_permissions_includes_task_permissions_for_employee_when_enabled(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            'employees_can_manage_tasks' => true,
        ]);
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => Role::Employee->value]);
        $permissionStore = new PermissionStore;
        $this->actingAs($user);

        // Act
        $result = $permissionStore->getPermissions($organization);

        // Assert
        $this->assertContains('tasks:create', $result);
        $this->assertContains('tasks:update', $result);
        $this->assertContains('tasks:delete', $result);
        $this->assertNotContains('tasks:create:all', $result);
        $this->assertNotContains('tasks:update:all', $result);
        $this->assertNotContains('tasks:delete:all', $result);
    }

    public function test_permission_checks_need_one_query_per_request(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => Role::Employee->value]);
        $permissionStore = new PermissionStore;
        $this->actingAs($user);
        DB::enableQueryLog();

        // Act
        $permissionStore->isMember($organization);
        $permissionStore->has($organization, 'time-entries:view:own');
        $permissionStore->has($organization, 'time-entries:view:all');
        $permissionStore->getPermissions($organization);
        $permissionStore->getMember($organization, $user);

        // Assert
        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_is_member_returns_false_when_user_does_not_belong_to_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $permissionStore = new PermissionStore;
        $this->actingAs($user);

        // Act
        $result = $permissionStore->isMember($organization);

        // Assert
        $this->assertFalse($result);
    }

    public function test_is_member_returns_false_when_user_is_not_authenticated(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $permissionStore = new PermissionStore;

        // Act
        $result = $permissionStore->isMember($organization);

        // Assert
        $this->assertFalse($result);
    }

    public function test_membership_created_after_a_failed_check_is_picked_up(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $permissionStore = new PermissionStore;
        $this->actingAs($user);
        $this->assertFalse($permissionStore->has($organization, 'time-entries:view:own'));
        $organization->users()->attach($user, ['role' => Role::Employee->value]);

        // Act
        $result = $permissionStore->has($organization, 'time-entries:view:own');

        // Assert
        $this->assertTrue($result);
    }

    public function test_get_member_returns_the_membership_of_the_user(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => Role::Employee->value]);
        $permissionStore = new PermissionStore;

        // Act
        $member = $permissionStore->getMember($organization, $user);

        // Assert
        $this->assertNotNull($member);
        $this->assertSame($user->getKey(), $member->user_id);
        $this->assertSame($organization->getKey(), $member->organization_id);
        $this->assertSame(Role::Employee->value, $member->role);
    }

    public function test_get_member_returns_null_when_user_does_not_belong_to_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $permissionStore = new PermissionStore;

        // Act
        $member = $permissionStore->getMember($organization, $user);

        // Assert
        $this->assertNull($member);
    }
}
