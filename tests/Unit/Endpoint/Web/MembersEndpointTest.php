<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use Inertia\Testing\AssertableInertia as Assert;

class MembersEndpointTest extends EndpointTestAbstract
{
    public function test_members_passes_available_roles_as_objects_with_key_name_and_description(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:view',
        ]);
        $this->actingAs($data->user);

        // Act
        $response = $this->get(route('members'));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Members')
            ->has('availableRoles', 5, fn (Assert $role) => $role
                ->has('key')
                ->has('name')
                ->has('description')
            )
            ->where('availableRoles.0.key', 'owner')
            ->where('availableRoles.0.name', 'Owner')
        );
    }
}
