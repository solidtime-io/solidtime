<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTeamNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_names_can_be_updated(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->put('/teams/'.$user->currentTeam->id, [
            'name' => 'Test Organization',
            'currency' => 'USD',
        ]);

        // Assert
        $response->assertValid(errorBag: 'updateTeamName');
        $this->assertCount(1, $user->fresh()->ownedTeams);
        $organization = $user->currentTeam->fresh();
        $this->assertEquals('Test Organization', $organization->name);
        $this->assertEquals('USD', $organization->currency);
    }
}
