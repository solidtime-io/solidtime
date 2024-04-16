<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(EnsureEmailIsVerified::class)]
#[UsesClass(EnsureEmailIsVerified::class)]
class EnsureEmailIsVerifiedMiddlewareTest extends MiddlewareTestAbstract
{
    private function createTestRoute(): string
    {
        return Route::get('/test-route', function () {
            return 'test-response';
        })->middleware(EnsureEmailIsVerified::class)->uri;
    }

    public function test_guests_are_redirected_to_verification_notice_route(): void
    {
        // Arrange
        $route = $this->createTestRoute();

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_users_with_unverified_email_are_redirected_to_verification_notice_route(): void
    {
        // Arrange
        $user = User::factory()->unverified()->create();
        $route = $this->createTestRoute();
        $this->actingAs($user);

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_users_with_verified_email_can_access_route(): void
    {
        // Arrange
        $user = User::factory()->create();
        $route = $this->createTestRoute();
        $this->actingAs($user);

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertOk();
    }

    public function test_users_with_unverified_email_can_access_route_in_local_environment(): void
    {
        // Arrange
        $user = User::factory()->unverified()->create();
        $route = $this->createTestRoute();
        $this->actingAs($user);
        $this->app->detectEnvironment(fn () => 'local');

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertOk();
    }
}
