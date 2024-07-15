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

    public function test_team_member_roles_can_no_longer_be_updated_over_jetstream(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        $user->currentTeam->users()->attach(
            $otherUser = User::factory()->create(), ['role' => 'admin']
        );

        // Act
        $response = $this->put('/teams/'.$user->currentTeam->id.'/members/'.$otherUser->id, [
            'role' => Role::Employee->value,
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertSee('Moved to API');
    }
}
