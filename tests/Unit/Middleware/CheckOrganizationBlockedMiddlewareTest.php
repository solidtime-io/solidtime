<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckOrganizationBlocked;
use App\Models\Organization;
use App\Service\BillingContract;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CheckOrganizationBlocked::class)]
class CheckOrganizationBlockedMiddlewareTest extends MiddlewareTestAbstract
{
    private function createTestRoute(): void
    {
        Route::get('/test-route/{organization}', function (Organization $organization) {
            return response()->json(['message' => 'Test route', 'id' => $organization->getKey()]);
        })->middleware([StartSession::class, SubstituteBindings::class, CheckOrganizationBlocked::class]);

    }

    private function createTestRouteNoModelBinding(): string
    {
        $route = Route::get('/test-route', function () {
            return response()->json(['message' => 'Test route']);
        })->middleware([StartSession::class, SubstituteBindings::class, CheckOrganizationBlocked::class]);

        return $route->uri;
    }

    public function test_request_fails_if_organization_is_blocked_by_the_billing_system(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $this->createTestRoute();
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isBlocked')->andReturn(true)->once();
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->get('/test-route/'.$user->organization->getKey());

        // Assert
        $response->assertStatus(400);
        $response->assertJson(['message' => 'Organization has no subscription but multiple members']);
    }

    public function test_request_fails_if_organization_is_not_found(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $this->createTestRoute();
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isBlocked')->never();
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->get('/test-route/'.Str::uuid());

        // Assert
        $response->assertStatus(404);
    }

    public function test_request_fails_on_route_without_organization_model_binding(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $route = $this->createTestRouteNoModelBinding();
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isBlocked')->never();
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertStatus(500);
    }

    public function test_request_succeeds_if_organization_is_not_blocked_by_the_billing_system(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $this->createTestRoute();
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isBlocked')->andReturn(false)->once();
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->get('/test-route/'.$user->organization->getKey());

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Test route', 'id' => $user->organization->getKey()]);
    }
}
