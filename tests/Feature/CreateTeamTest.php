<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_teams_can_be_created(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->post('/teams', [
            'name' => 'Test Organization',
        ]);

        // Assert
        $newOrganization = $user->fresh()->ownedTeams()->latest('id')->first();
        $this->assertCount(2, $user->fresh()->ownedTeams);
        $this->assertEquals('Test Organization', $newOrganization->name);
        $member = Membership::query()->whereBelongsTo($user, 'user')->whereBelongsTo($newOrganization, 'organization')->firstOrFail();
        $this->assertSame(Role::Owner->value, $member->role);
    }
}
