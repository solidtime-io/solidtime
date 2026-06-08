<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Enums\Role;
use App\Http\Controllers\Web\OrganizationController;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(OrganizationController::class)]
class OrganizationEndpointTest extends EndpointTestAbstract
{
    public function test_organization_create_succeeds(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('organizations.create'));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Teams/Create')
        );
    }

    public function test_legacy_teams_create_redirects_to_new_organization_create(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('teams.create'));

        // Assert
        $response->assertRedirect(route('organizations.create'));
    }

    public function test_organization_show_succeeds(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:view',
        ]);
        $this->actingAs($data->user);

        // Act
        $response = $this->get(route('organizations.show', [$data->organization->getKey()]));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Teams/Show')
            ->where('team.id', $data->organization->getKey())
            ->where('team.name', $data->organization->name)
            ->where('team.currency', $data->organization->currency)
            ->where('team.owner.id', $data->owner->getKey())
            ->where('team.owner.name', $data->owner->name)
            ->has('team.owner.profile_photo_url')
            ->has('currencies')
        );
    }

    /**
     * @return array<string, array{role: Role, canUpdateTeam: bool, canDeleteTeam: bool}>
     */
    public static function showPermissionsPerRoleProvider(): array
    {
        return [
            'owner can update and delete' => ['role' => Role::Owner, 'canUpdateTeam' => true, 'canDeleteTeam' => true],
            'admin can update but not delete' => ['role' => Role::Admin, 'canUpdateTeam' => true, 'canDeleteTeam' => false],
            'employee can neither update nor delete' => ['role' => Role::Employee, 'canUpdateTeam' => false, 'canDeleteTeam' => false],
        ];
    }

    #[DataProvider('showPermissionsPerRoleProvider')]
    public function test_organization_show_returns_permissions_based_on_role(Role $role, bool $canUpdateTeam, bool $canDeleteTeam): void
    {
        // Arrange
        $data = $this->createUserWithRole($role);
        $this->actingAs($data->user);

        // Act
        $response = $this->get(route('organizations.show', [$data->organization->getKey()]));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Teams/Show')
            ->where('permissions.canUpdateTeam', $canUpdateTeam)
            ->where('permissions.canDeleteTeam', $canDeleteTeam)
        );
    }

    public function test_legacy_team_show_redirects_to_organization_show(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:view',
        ]);
        $this->actingAs($data->user);

        // Act
        $response = $this->get(route('teams.show', [$data->organization->getKey()]));

        // Assert
        $response->assertRedirect(route('organizations.show', [$data->organization->getKey()]));
    }

    public function test_team_show_redirects_to_dashboard_for_invalid_organization_id(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('organizations.show', ['not-a-uuid']));

        // Assert
        $response->assertRedirect(route('dashboard'));
    }

    public function test_organization_show_redirects_to_dashboard_for_unknown_organization_id(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('organizations.show', ['00000000-0000-4000-8000-000000000000']));

        // Assert
        $response->assertRedirect(route('dashboard'));
    }

    public function test_organization_show_redirects_to_dashboard_without_organization_view_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $this->actingAs($data->user);

        // Act
        $response = $this->get(route('organizations.show', [$data->organization->getKey()]));

        // Assert
        $response->assertRedirect(route('dashboard'));
    }

    public function test_organization_show_redirects_to_dashboard_for_organization_outside_user_memberships(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:view',
        ]);
        $otherOrganization = Organization::factory()->create();
        $this->actingAs($data->user);

        // Act
        $response = $this->get(route('organizations.show', [$otherOrganization->getKey()]));

        // Assert
        $response->assertRedirect(route('dashboard'));
    }

    public function test_organization_show_does_not_expose_member_roster_invitations_or_owner_email(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:view',
        ]);
        OrganizationInvitation::factory()->forOrganization($data->organization)->create([
            'email' => 'pending@example.com',
        ]);
        $this->actingAs($data->user);

        // Act
        $response = $this->get(route('organizations.show', [$data->organization->getKey()]));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('team.users')
            ->missing('team.team_invitations')
            ->missing('team.owner.email')
            ->has('team.owner.id')
            ->has('team.owner.name')
            ->has('team.owner.profile_photo_url')
        );
    }
}
