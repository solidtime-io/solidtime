<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Models\OrganizationInvitation;
use App\Providers\JetstreamServiceProvider;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Jetstream\Jetstream;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(JetstreamServiceProvider::class)]
class TeamShowEndpointTest extends EndpointTestAbstract
{
    protected function setUp(): void
    {
        Jetstream::$inertiaManager = null;
        parent::setUp();
    }

    public function test_team_show_does_not_expose_member_roster_invitations_or_owner_email(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        OrganizationInvitation::factory()->forOrganization($data->organization)->create([
            'email' => 'pending@example.com',
        ]);
        $this->actingAs($data->user);

        // Act
        $response = $this->get('/teams/'.$data->organization->getKey());

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('team.users')
            ->missing('team.team_invitations')
            ->missing('team.owner.email')
            ->has('team.owner.id')
            ->has('team.owner.name')
            ->has('team.owner.profile_photo_url')
        );
    }
}
