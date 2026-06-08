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

    public function test_team_member_invitations_can_be_accepted(): void
    {
        // Arrange
        Mail::fake();
        $owner = User::factory()->withPersonalOrganization()->create();
        $user = User::factory()->withPersonalOrganization()->create();
        $invitation = $owner->currentOrganization->organizationInvitations()->create([
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
        $this->assertCount(0, $owner->currentOrganization->fresh()->organizationInvitations);
        $user->refresh();
        $this->assertCount(2, $user->organizations);
        $this->assertContains($owner->currentOrganization->getKey(), $user->organizations->pluck('id'));
    }

    public function test_team_member_invitations_of_placeholder_can_be_accepted_and_migrates_date_to_real_user(): void
    {
        // Arrange
        Mail::fake();
        $placeholder = User::factory()->placeholder()->create();
        $owner = User::factory()->withPersonalOrganization()->create();
        $placeholderMember = Member::factory()->role(Role::Placeholder)->forOrganization($owner->currentOrganization)->forUser($placeholder)->create();

        $timeEntries = TimeEntry::factory()->forOrganization($owner->currentOrganization)->forMember($placeholderMember)->createMany(5);

        $user = User::factory()->withPersonalOrganization()->create([
            'email' => $placeholder->email,
        ]);

        $invitation = $owner->currentOrganization->organizationInvitations()->create([
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
        $this->assertCount(0, $owner->currentOrganization->fresh()->organizationInvitations);
        $this->assertCount(2, $user->organizations);
        $this->assertContains($owner->currentOrganization->getKey(), $user->organizations->pluck('id'));
        $this->assertCount(5, $user->timeEntries);
    }

    public function test_team_member_accept_fails_if_user_with_that_email_does_not_exist(): void
    {
        // Arrange
        Mail::fake();
        $owner = User::factory()->withPersonalOrganization()->create();
        $user = User::factory()->withPersonalOrganization()->create();
        $invitation = $owner->currentOrganization->organizationInvitations()->create([
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
        $this->assertCount(1, $owner->currentOrganization->fresh()->organizationInvitations);
        $user->refresh();
        $this->assertCount(1, $user->organizations);
    }
}
