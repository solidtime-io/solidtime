<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Mail\TeamInvitation;
use Tests\TestCase;

class InviteTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_members_can_be_invited_to_team(): void
    {
        // Arrange
        Mail::fake();
        $this->actingAs($user = User::factory()->withPersonalOrganization()->create());

        // Act
        $response = $this->post('/teams/'.$user->currentTeam->id.'/members', [
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        // Assert
        Mail::assertSent(TeamInvitation::class);
        $this->assertCount(1, $user->currentTeam->fresh()->teamInvitations);
    }

    public function test_team_member_invitations_can_be_cancelled(): void
    {
        // Arrange
        Mail::fake();

        $this->actingAs($user = User::factory()->withPersonalOrganization()->create());

        $invitation = $user->currentTeam->teamInvitations()->create([
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        // Act
        $response = $this->delete('/team-invitations/'.$invitation->id);

        // Assert
        $this->assertCount(0, $user->currentTeam->fresh()->teamInvitations);
    }
}
