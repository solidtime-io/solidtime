<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Member;
use App\Models\Organization;
use Laravel\Passport\Passport;

class UserMembershipEndpointTest extends ApiEndpointTestAbstract
{
    public function test_my_members_fails_when_not_authenticated(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.users.memberships.my-memberships'));

        // Assert
        $response->assertUnauthorized();
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_my_members_returns_information_about_the_organization_membership_of_the_current_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $otherOrganization = Organization::factory()->create();
        $otherMember = Member::factory()->forOrganization($otherOrganization)->forUser($data->user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.users.memberships.my-memberships'));

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
        $otherMemberResponse = collect($response->json('data'))->where('id', '=', $otherMember->getKey())->first();
        $this->assertNotNull($otherMemberResponse);
        $this->assertSame($otherMember->organization->getKey(), $otherMemberResponse['organization']['id']);
        $memberResponse = collect($response->json('data'))->where('id', '=', $data->member->getKey())->first();
        $this->assertNotNull($memberResponse);
    }

    public function test_my_members_returns_the_memberships_ordered_from_oldest_to_newest(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $data->member->forceFill(['created_at' => now()->subDays(2)])->save();
        $newerMember = Member::factory()
            ->forOrganization(Organization::factory()->create())
            ->forUser($data->user)
            ->create(['created_at' => now()]);
        $olderMember = Member::factory()
            ->forOrganization(Organization::factory()->create())
            ->forUser($data->user)
            ->create(['created_at' => now()->subDay()]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.users.memberships.my-memberships'));

        // Assert
        $response->assertSuccessful();
        $this->assertSame([
            $data->member->getKey(),
            $olderMember->getKey(),
            $newerMember->getKey(),
        ], array_column($response->json('data'), 'id'));
    }
}
