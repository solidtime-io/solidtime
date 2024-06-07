<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_accounts_can_be_deleted(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->delete('/user', [
            'password' => 'password',
        ]);

        // Assert
        $response->assertStatus(302);
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_before_account_can_be_deleted(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->delete('/user', [
            'password' => 'wrong-password',
        ]);

        // Assert
        $this->assertNotNull($user->fresh());
    }

    public function test_user_account_can_not_be_deleted_if_attached_to_a_organization_with_multiple_users(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->withOwner($user)->create();
        $userMember = Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Owner)->create();
        $otherUser = User::factory()->create();
        $otherMember = Member::factory()->forOrganization($organization)->forUser($otherUser)->role(Role::Admin)->create();
        $this->actingAs($user);

        // Act
        $response = $this->delete('/user', [
            'password' => 'password',
        ]);

        // Assert
        $response->assertInvalid(['password']);
        $this->assertNotNull($user->fresh());
    }
}
