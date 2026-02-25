<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Enums\Role;
use App\Http\Controllers\Web\OrganizationInvitationController;
use App\Models\Member;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Service\MemberService;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(OrganizationInvitationController::class)]
#[CoversClass(MemberService::class)]
class OrganizationInvitationEndpointTest extends EndpointTestAbstract
{
    public function test_legacy_url_still_works(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $invitation = OrganizationInvitation::factory()
            ->forOrganization($user->organization)
            ->create();

        // Act
        $acceptUrl = URL::temporarySignedRoute(
            'team-invitations.accept',
            now()->addMinutes(60),
            [$invitation->getKey()]
        );
        $response = $this->get($acceptUrl);

        // Assert
        $response->assertValid();
        $response->assertRedirect(route('register', [
            'bannerStyle' => 'info',
            'bannerText' => 'Please create an account to finish joining the '.$user->organization->name.' organization.',
        ]));
        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
    }

    public function test_can_accept_invitation_without_an_account_with_the_email_address_and_redirects_to_registration(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $invitation = OrganizationInvitation::factory()
            ->forOrganization($user->organization)
            ->create();

        // Act
        $acceptUrl = URl::to(URL::temporarySignedRoute(
            'organization-invitations.accept',
            now()->addMinutes(60),
            [$invitation->getKey()],
            false
        ));
        $response = $this->get($acceptUrl);

        // Assert
        $response->assertValid();
        $response->assertRedirect(route('register', [
            'bannerStyle' => 'info',
            'bannerText' => 'Please create an account to finish joining the '.$user->organization->name.' organization.',
        ]));
        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
    }

    public function test_can_accept_invitation_with_an_account_with_the_email_address_and_redirects_to_dashboard(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $user2 = $this->createUserWithPermission();
        $invitation = OrganizationInvitation::factory()
            ->forOrganization($user->organization)
            ->create([
                'role' => Role::Employee->value,
                'email' => $user2->user->email,
            ]);
        $this->actingAs($user2->user);

        // Act
        $acceptUrl = URl::to(URL::temporarySignedRoute(
            'organization-invitations.accept',
            now()->addMinutes(60),
            [$invitation->getKey()],
            false
        ));
        $response = $this->get($acceptUrl);

        // Assert
        $response->assertValid();
        $response->assertRedirect(route('dashboard', [
            'bannerStyle' => 'success',
            'bannerText' => 'Great! You have accepted the invitation to join the '.$user->organization->name.' organization.',
        ]));
        $this->assertDatabaseHas(Member::class, [
            'user_id' => $user2->user->getKey(),
            'organization_id' => $user->organization->getKey(),
            'role' => Role::Employee->value,
        ]);
        $this->assertDatabaseMissing(OrganizationInvitation::class, [
            'id' => $invitation->getKey(),
        ]);
    }

    public function test_fails_if_user_is_already_member_of_the_organization(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $user2 = $this->createUserWithPermission();
        $invitation = OrganizationInvitation::factory()
            ->forOrganization($user->organization)
            ->create([
                'role' => Role::Employee->value,
                'email' => $user2->user->email,
            ]);
        Member::factory()->forOrganization($user->organization)->forUser($user2->user)->create();
        $this->actingAs($user2->user);

        // Act
        $acceptUrl = URl::to(URL::temporarySignedRoute(
            'organization-invitations.accept',
            now()->addMinutes(60),
            [$invitation->getKey()],
            false
        ));
        $response = $this->get($acceptUrl);

        // Assert
        $response->assertValid();
        $response->assertRedirect(route('dashboard', [
            'bannerStyle' => 'danger',
            'bannerText' => 'You are already a member of the '.$user->organization->name.' organization.',
        ]));
    }

    public function test_accepting_invitation_with_existing_account_migrates_data_of_placeholder_users_with_same_email_to_new_member(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $user2 = $this->createUserWithPermission();
        $invitation = OrganizationInvitation::factory()
            ->forOrganization($user->organization)
            ->create([
                'role' => Role::Employee->value,
                'email' => $user2->user->email,
            ]);
        $placeholder1 = User::factory()->placeholder()->create([
            'email' => $user2->user->email,
        ]);
        $placeholder1Member = Member::factory()->forOrganization($user->organization)->forUser($placeholder1)->role(Role::Placeholder)->create();
        $placeholder2 = User::factory()->placeholder()->create([
            'email' => $user2->user->email,
        ]);
        $placeholder2Member = Member::factory()->forOrganization($user->organization)->forUser($placeholder2)->role(Role::Placeholder)->create();

        $this->actingAs($user2->user);

        // Act
        $acceptUrl = URl::to(URL::temporarySignedRoute(
            'organization-invitations.accept',
            now()->addMinutes(60),
            [$invitation->getKey()],
            false
        ));
        $response = $this->get($acceptUrl);

        // Assert
        $response->assertValid();
        $response->assertRedirect(route('dashboard', [
            'bannerStyle' => 'success',
            'bannerText' => 'Great! You have accepted the invitation to join the '.$user->organization->name.' organization.',
        ]));
        $this->assertDatabaseHas(Member::class, [
            'user_id' => $user2->user->getKey(),
            'organization_id' => $user->organization->getKey(),
            'role' => Role::Employee->value,
        ]);
        $this->assertDatabaseMissing(User::class, [
            'id' => $placeholder1->getKey(),
        ]);
        $this->assertDatabaseMissing(User::class, [
            'id' => $placeholder2->getKey(),
        ]);
        $this->assertDatabaseMissing(Member::class, [
            'id' => $placeholder1Member->getKey(),
        ]);
        $this->assertDatabaseMissing(Member::class, [
            'id' => $placeholder2Member->getKey(),
        ]);
        $this->assertDatabaseMissing(OrganizationInvitation::class, [
            'id' => $invitation->getKey(),
        ]);
    }

    public function test_fails_with_invalid_signature(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $invitation = OrganizationInvitation::factory()
            ->forOrganization($user->organization)
            ->create();

        // Act
        $response = $this->get(URL::temporarySignedRoute(
            'organization-invitations.accept',
            now()->addMinutes(60),
            [$invitation->getKey()]).
            '?invalid'
        );

        // Assert
        $response->assertForbidden();
    }
}
