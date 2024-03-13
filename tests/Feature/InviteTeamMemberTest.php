<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
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

    public function test_team_member_can_not_be_invited_to_team_if_already_on_team(): void
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->withPersonalOrganization()->create();
        $existingUser = User::factory()->create();
        $user->currentTeam->users()->attach($existingUser, ['role' => 'admin']);
        $this->actingAs($user);

        // Act
        $response = $this->post('/teams/'.$user->currentTeam->id.'/members', [
            'email' => $existingUser->email,
            'role' => 'admin',
        ]);

        // Assert
        $response->assertInvalid(['email'], 'addTeamMember');
        Mail::assertNotSent(TeamInvitation::class);
        $this->assertCount(0, $user->currentTeam->fresh()->teamInvitations);
    }

    public function test_team_member_can_be_invited_to_team_if_already_on_team_as_placeholder(): void
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->withPersonalOrganization()->create();
        $existingUser = User::factory()->create([
            'is_placeholder' => true,
        ]);
        $user->currentTeam->users()->attach($existingUser, ['role' => 'employee']);
        $this->actingAs($user);

        // Act
        $response = $this->post('/teams/'.$user->currentTeam->id.'/members', [
            'email' => $existingUser->email,
            'role' => 'employee',
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

    public function test_team_member_invitations_can_be_accepted(): void
    {
        // Arrange
        Mail::fake();
        $owner = User::factory()->withPersonalOrganization()->create();
        $user = User::factory()->withPersonalOrganization()->create();
        $invitation = $owner->currentTeam->teamInvitations()->create([
            'email' => $user->email,
            'role' => 'employee',
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
        $this->assertCount(1, $user->organizations);
        $this->assertContains($owner->currentTeam->getKey(), $user->organizations->pluck('id'));
    }

    public function test_team_member_invitations_of_placeholder_can_be_accepted_and_migrates_date_to_real_user(): void
    {
        // Arrange
        Mail::fake();
        $placeholder = User::factory()->withPersonalOrganization()->create([
            'is_placeholder' => true,
        ]);

        $owner = User::factory()->withPersonalOrganization()->create();
        $owner->currentTeam->users()->attach($placeholder, ['role' => 'employee']);
        $timeEntries = TimeEntry::factory()->forOrganization($owner->currentTeam)->forUser($placeholder)->createMany(5);

        $user = User::factory()->withPersonalOrganization()->create([
            'email' => $placeholder->email,
        ]);

        $invitation = $owner->currentTeam->teamInvitations()->create([
            'email' => $user->email,
            'role' => 'employee',
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
        $user->refresh();
        $placeholder->refresh();
        $this->assertCount(0, $owner->currentTeam->fresh()->teamInvitations);
        $this->assertCount(1, $user->organizations);
        $this->assertContains($owner->currentTeam->getKey(), $user->organizations->pluck('id'));
        $this->assertCount(5, $user->timeEntries);
        $this->assertCount(0, $placeholder->timeEntries);
    }

    public function test_team_member_accept_fails_if_user_with_that_email_does_not_exist(): void
    {
        // Arrange
        Mail::fake();
        $owner = User::factory()->withPersonalOrganization()->create();
        $user = User::factory()->withPersonalOrganization()->create();
        $invitation = $owner->currentTeam->teamInvitations()->create([
            'email' => 'firstname.lastname@mail.test',
            'role' => 'employee',
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
        $this->assertCount(0, $user->organizations);
    }
}
