<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use App\Service\PermissionStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Jetstream;
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
        $this->assertSame(Jetstream::findRole(Role::Employee->value)->permissions, $result);
    }
}
