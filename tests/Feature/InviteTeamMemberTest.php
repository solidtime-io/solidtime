<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Member;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InviteTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_members_can_no_longer_be_invited_to_team_over_jetstream(): void
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
        $response->assertStatus(403);
        $response->assertSee('Moved to API');
        Mail::assertNothingSent();
    }

    public function test_team_member_invitations_can_no_longer_be_cancelled_over_jetstream(): void
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
        $response->assertStatus(403);
        $this->assertCount(1, $user->currentTeam->fresh()->teamInvitations);
    }

    public function test_team_member_invitations_can_be_accepted(): void
    {
        // Arrange
        Mail::fake();
        $owner = User::factory()->withPersonalOrganization()->create();
        $user = User::factory()->withPersonalOrganization()->create();
        $invitation = $owner->currentTeam->teamInvitations()->create([
            'email' => $user->email,
            'role' => Role::Employee->value,
        ]);
        $this->actingAs($user);

        // Act
        $acceptUrl = URL::temporarySignedRoute(
            'team-invitations.accept',
            now()->addMinutes(60),
            [$invitation->getKey()]
        );
        $response = $this->get($acceptUrl);

        // Assert
        $this->assertCount(0, $owner->currentTeam->fresh()->teamInvitations);
        $user->refresh();
        $this->assertCount(2, $user->organizations);
        $this->assertContains($owner->currentTeam->getKey(), $user->organizations->pluck('id'));
    }

    public function test_team_member_invitations_of_placeholder_can_be_accepted_and_migrates_date_to_real_user(): void
    {
        // Arrange
        Mail::fake();
        $placeholder = User::factory()->placeholder()->create();
        $owner = User::factory()->withPersonalOrganization()->create();
        $placeholderMember = Member::factory()->forOrganization($owner->currentTeam)->forUser($placeholder)->create();

        $timeEntries = TimeEntry::factory()->forOrganization($owner->currentTeam)->forMember($placeholderMember)->createMany(5);

        $user = User::factory()->withPersonalOrganization()->create([
            'email' => $placeholder->email,
        ]);

        $invitation = $owner->currentTeam->teamInvitations()->create([
            'email' => $user->email,
            'role' => Role::Employee->value,
        ]);
        $this->actingAs($user);

        // Act
        $acceptUrl = URL::temporarySignedRoute(
            'team-invitations.accept',
            now()->addMinutes(60),
            [$invitation->getKey()]
        );
        $response = $this->get($acceptUrl);

        // Assert
        $response->assertRedirect();
        $user->refresh();
        $this->assertDatabaseMissing(User::class, ['id' => $placeholder->id]);
        $this->assertCount(0, $owner->currentTeam->fresh()->teamInvitations);
        $this->assertCount(2, $user->organizations);
        $this->assertContains($owner->currentTeam->getKey(), $user->organizations->pluck('id'));
        $this->assertCount(5, $user->timeEntries);
    }

    public function test_team_member_accept_fails_if_user_with_that_email_does_not_exist(): void
    {
        // Arrange
        Mail::fake();
        $owner = User::factory()->withPersonalOrganization()->create();
        $user = User::factory()->withPersonalOrganization()->create();
        $invitation = $owner->currentTeam->teamInvitations()->create([
            'email' => 'firstname.lastname@mail.test',
            'role' => Role::Employee->value,
        ]);
        $this->actingAs($user);

        // Act
        $acceptUrl = URL::temporarySignedRoute(
            'team-invitations.accept',
            now()->addMinutes(60),
            [$invitation->getKey()]
        );
        $response = $this->get($acceptUrl);

        // Assert
        $this->assertCount(1, $owner->currentTeam->fresh()->teamInvitations);
        $user->refresh();
        $this->assertCount(1, $user->organizations);
    }
}
