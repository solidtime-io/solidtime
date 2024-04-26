<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTeamMemberRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_member_roles_can_be_updated(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        $user->currentTeam->users()->attach(
            $otherUser = User::factory()->create(), ['role' => 'admin']
        );

        // Act
        $response = $this->put('/teams/'.$user->currentTeam->id.'/members/'.$otherUser->id, [
            'role' => 'employee',
        ]);

        // Assert
        $this->assertTrue($otherUser->fresh()->hasTeamRole(
            $user->currentTeam->fresh(), 'employee'
        ));
    }

    public function test_team_member_roles_can_not_be_updated_to_placeholder(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        $user->currentTeam->users()->attach(
            $otherUser = User::factory()->create(), ['role' => 'admin']
        );

        // Act
        $response = $this->put('/teams/'.$user->currentTeam->id.'/members/'.$otherUser->id, [
            'role' => 'placeholder',
        ]);

        // Assert
        $this->assertTrue($otherUser->fresh()->hasTeamRole(
            $user->currentTeam->fresh(), 'admin'
        ));
    }

    public function test_team_member_roles_can_be_updated_to_owner_which_changes_ownership(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);
        $otherUser = User::factory()->create();
        $user->currentTeam->users()->attach($otherUser, ['role' => 'admin']);

        // Act
        $response = $this->withoutExceptionHandling()->put('/teams/'.$user->currentTeam->id.'/members/'.$otherUser->getKey(), [
            'role' => Role::Owner->value,
        ]);

        // Assert
        $this->assertTrue($otherUser->fresh()->hasTeamRole(
            $user->currentTeam->fresh(), Role::Owner->value
        ));
        $this->assertSame($user->currentTeam->fresh()->user_id, $otherUser->getKey());
    }

    public function test_only_team_owner_can_update_team_member_roles(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();

        $user->currentTeam->users()->attach(
            $otherUser = User::factory()->create(), ['role' => 'admin']
        );

        $this->actingAs($otherUser);

        // Act
        $response = $this->put('/teams/'.$user->currentTeam->id.'/members/'.$otherUser->id, [
            'role' => 'employee',
        ]);

        // Assert
        $response->assertStatus(403);
        $this->assertTrue($otherUser->fresh()->hasTeamRole(
            $user->currentTeam->fresh(), 'admin'
        ));
    }
}
