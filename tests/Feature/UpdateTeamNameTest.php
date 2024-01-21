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
        $this->actingAs($user = User::factory()->withPersonalOrganization()->create());

        $response = $this->put('/teams/'.$user->currentTeam->id, [
            'name' => 'Test Organization',
        ]);

        $this->assertCount(1, $user->fresh()->ownedTeams);
        $this->assertEquals('Test Organization', $user->currentTeam->fresh()->name);
    }
}
