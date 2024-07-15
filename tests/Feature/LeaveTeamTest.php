<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_no_longer_leave_team_over_jetstream(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();

        $user->currentTeam->users()->attach(
            $otherUser = User::factory()->create(), ['role' => 'admin']
        );

        $this->actingAs($otherUser);

        // Act
        $response = $this->delete('/teams/'.$user->currentTeam->id.'/members/'.$otherUser->id);

        // Assert
        $response->assertStatus(403);
        $this->assertCount(2, $user->currentTeam->fresh()->users);
    }
}
