<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Models\User;

class DashboardEndpointTest extends EndpointTestAbstract
{
    public function test_showing_dashboard_succeeds_for_empty_user_with_no_data_entries(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get('/dashboard');

        // Assert
        $response->assertSuccessful();
    }
}
