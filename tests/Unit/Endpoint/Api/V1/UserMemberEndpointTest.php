<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Member;
use App\Models\Organization;
use Laravel\Passport\Passport;

class UserMemberEndpointTest extends ApiEndpointTestAbstract
{
    public function test_my_members_fails_when_not_authenticated(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.users.members.my-members'));

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
        $response = $this->getJson(route('api.v1.users.members.my-members'));

        // Assert
        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
        $otherMemberResponse = collect($response->json('data'))->where('id', '=', $otherMember->getKey())->first();
        $this->assertNotNull($otherMemberResponse);
        $this->assertSame($otherMember->organization->getKey(), $otherMemberResponse['organization']['id']);
        $memberResponse = collect($response->json('data'))->where('id', '=', $data->member->getKey())->first();
        $this->assertNotNull($memberResponse);
    }
}
