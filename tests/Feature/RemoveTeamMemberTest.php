<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_members_can_no_longer_be_removed_from_teams_over_jetstream_endpoints(): void
    {
        // Arrange
        $this->actingAs($user = User::factory()->withPersonalOrganization()->create());

        $user->currentTeam->users()->attach(
            $otherUser = User::factory()->create(), ['role' => 'admin']
        );

        // Act
        $response = $this->delete('/teams/'.$user->currentTeam->id.'/members/'.$otherUser->id);

        // Assert
        $response->assertStatus(403);
        $response->assertSee('Moved to API');
    }
}
