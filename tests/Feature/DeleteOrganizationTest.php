<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizations_can_be_deleted_and_users_of_the_organization_that_have_no_organization_get_a_new_one(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->withOwner($user)->create([
            'personal_team' => false,
        ]);
        Member::factory()->forOrganization($organization)->forUser($user)->role(Role::Owner)->create();

        $otherUser = User::factory()->create();
        $organization->users()->attach(
            $otherUser, ['role' => 'test-role']
        );

        // Act
        $response = $this->withoutExceptionHandling()->delete('/teams/'.$organization->getKey());

        // Assert
        $this->assertNull($organization->fresh());
        $this->assertCount(1, $otherUser->fresh()->teams);
        $this->assertFalse($otherUser->fresh()->teams->first()->is($organization));
    }

    public function test_personal_organizations_can_be_deleted_but_user_gets_an_new_one_if_this_is_the_only_one_left(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $organization = $user->currentTeam;
        $this->actingAs($user);

        // Act
        $response = $this->delete('/teams/'.$organization->getKey());

        // Assert
        $user->refresh();
        $this->assertDatabaseMissing(Organization::class, [
            'id' => $organization->getKey(),
        ]);
        $this->assertTrue($user->currentTeam->isNot($organization));
    }

    public function test_organization_can_not_be_deleted_if_user_is_not_owner(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $organization = Organization::factory()->withOwner($user)->create([
            'personal_team' => false,
        ]);
        $this->actingAs($user);

        $otherUser = User::factory()->create();
        $organization->users()->attach(
            $otherUser, ['role' => Role::Admin->value]
        );

        // Act
        $response = $this->delete('/teams/'.$organization->getKey());

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Organization::class, [
            'id' => $organization->getKey(),
        ]);
    }
}
