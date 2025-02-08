<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Http\Controllers\Web\HomeController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(HomeController::class)]
#[CoversClass(Authenticate::class)]
#[CoversClass(RedirectIfAuthenticated::class)]
#[UsesClass(HomeController::class)]
class HomeEndpointTest extends EndpointTestAbstract
{
    public function test_index_redirects_to_dashboard_if_user_is_logged_in(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get('/');

        // Assert
        $response->assertRedirect('/dashboard');
    }

    public function test_index_redirects_to_login_if_user_is_not_logged_in(): void
    {
        // Arrange

        // Act
        $response = $this->get('/');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_login_redirects_to_dashboard_if_user_is_logged_in(): void
    {
        // Arrange
        $user = User::factory()->withPersonalOrganization()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get('/login');

        // Assert
        $response->assertRedirect('/dashboard');
    }
}
