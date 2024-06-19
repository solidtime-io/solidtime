<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\Role;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Models\OrganizationInvitation;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Mail\TeamInvitation;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(InvitationController::class)]
class InvitationEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_fails_if_user_has_no_permission_to_view_invitations(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.invitations.index', $data->organization->id));

        // Assert
        $response->assertStatus(403);
    }

    public function test_index_returns_invitations_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'invitations:view',
        ]);
        $invitation1 = OrganizationInvitation::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.invitations.index', $data->organization->getKey()));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                [
                    'id' => $invitation1->getKey(),
                    'email' => $invitation1->email,
                    'role' => $invitation1->role,
                ],
            ],
        ]);
    }

    public function test_store_fails_if_user_has_no_permission_to_create_invitations(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.invitations.store', $data->organization->getKey()), [
            'email' => 'test@mail.test',
            'role' => Role::Employee->value,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_store_fails_if_user_invites_with_role_owner(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'invitations:create',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.invitations.store', $data->organization->getKey()), [
            'email' => 'test@asdf.at',
            'role' => Role::Owner->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The selected role is invalid.');
    }

    public function test_store_fails_if_user_invites_with_role_placeholder(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'invitations:create',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.invitations.store', $data->organization->getKey()), [
            'email' => 'test@asdf.at',
            'role' => Role::Placeholder->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The selected role is invalid.');
    }

    public function test_store_invites_user_to_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'invitations:create',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.invitations.store', $data->organization->getKey()), [
            'email' => 'test@asdf.at',
            'role' => Role::Employee->value,
        ]);

        // Assert
        $response->assertStatus(204);
        $invitation = OrganizationInvitation::first();
        $this->assertNotNull($invitation);
        $this->assertEquals('test@asdf.at', $invitation->email);
        $this->assertEquals(Role::Employee->value, $invitation->role);
    }

    public function test_resend_fails_if_user_has_no_permission_to_resend_the_invitation(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);
        $invitation = OrganizationInvitation::factory()->forOrganization($data->organization)->create();

        // Act
        $response = $this->postJson(route('api.v1.invitations.resend', [
            $data->organization->getKey(),
            $invitation->getKey(),
        ]));

        // Assert
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        $response->assertStatus(403);
    }

    public function test_resend_fails_if_invitation_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'invitations:resend',
        ]);
        Passport::actingAs($data->user);
        $invitation = OrganizationInvitation::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.invitations.resend', [$data->organization->getKey(), $invitation->getKey()]));

        // Assert
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        $response->assertStatus(403);
    }

    public function test_resend_resends_invitation_email(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'invitations:resend',
        ]);
        Passport::actingAs($data->user);
        $invitation = OrganizationInvitation::factory()->forOrganization($data->organization)->create();

        // Act
        $response = $this->postJson(route('api.v1.invitations.resend', [
            $data->organization->getKey(),
            $invitation->getKey(),
        ]));

        // Assert
        Mail::assertSent(fn (TeamInvitation $mail): bool => $mail->invitation->is($invitation));
        Mail::assertNothingQueued();
        $response->assertStatus(204);
    }

    public function test_delete_fails_if_user_has_no_permission_to_remove_invitations(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);
        $invitation = OrganizationInvitation::factory()->forOrganization($data->organization)->create();

        // Act
        $response = $this->deleteJson(route('api.v1.invitations.destroy', [$data->organization->getKey(), $invitation->getKey()]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_delete_fails_if_invitation_belongs_to_different_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'invitations:remove',
        ]);
        Passport::actingAs($data->user);
        $invitation = OrganizationInvitation::factory()->create();

        // Act
        $response = $this->deleteJson(route('api.v1.invitations.destroy', [$data->organization->getKey(), $invitation->getKey()]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_delete_removes_invitation(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'invitations:remove',
        ]);
        Passport::actingAs($data->user);
        $invitation = OrganizationInvitation::factory()->forOrganization($data->organization)->create();

        // Act
        $response = $this->deleteJson(route('api.v1.invitations.destroy', [$data->organization->getKey(), $invitation->getKey()]));

        // Assert
        $response->assertStatus(204);
        $this->assertNull(OrganizationInvitation::find($invitation->getKey()));
    }
}
