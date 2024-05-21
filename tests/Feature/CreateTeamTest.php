<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Member;
use App\Models\Organization;
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
        sleep(1);

        // Act
        $response = $this->post('/teams', [
            'name' => 'Test Organization',
        ]);

        // Assert
        /** @var Organization|null $newOrganization */
        $ownedTeams = $user->fresh()->ownedTeams;
        $this->assertCount(2, $ownedTeams);
        $this->assertTrue($ownedTeams->contains('name', 'Test Organization'));
        $newOrganization = $ownedTeams->firstWhere('name', 'Test Organization');
        /** @var Member $member */
        $member = Member::query()->whereBelongsTo($user, 'user')->whereBelongsTo($newOrganization, 'organization')->firstOrFail();
        $this->assertSame(Role::Owner->value, $member->role);
    }
}
