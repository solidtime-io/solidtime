<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Enums\Role;
use App\Http\Controllers\Web\DashboardController;
use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(DashboardController::class)]
#[UsesClass(DashboardController::class)]
class DashboardEndpointTest extends EndpointTestAbstract
{
    public function test_showing_dashboard_succeeds_for_empty_user(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get('/dashboard');

        // Assert
        $response->assertSuccessful();
    }

    public function test_showing_dashboard_succeeds_for_user_with_employee_role(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->forCurrentOrganization($organization)->create();
        $organization->users()->attach($user, ['role' => Role::Employee->value]);
        $this->actingAs($user);

        // Act
        $response = $this->get('/dashboard');

        // Assert
        $response->assertSuccessful();
    }
}
