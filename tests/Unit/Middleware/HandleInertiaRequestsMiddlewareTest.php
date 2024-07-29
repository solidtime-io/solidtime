<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\HandleInertiaRequests;
use App\Service\BillingContract;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(HandleInertiaRequests::class)]
#[UsesClass(HandleInertiaRequests::class)]
class HandleInertiaRequestsMiddlewareTest extends MiddlewareTestAbstract
{
    private function createTestRoute(): string
    {
        return Route::get('/test-route', function () {
            return Inertia::render('Welcome');
        })->middleware([StartSession::class, HandleInertiaRequests::class])->uri;
    }

    public function test_adds_billing_information_to_shared_data_of_inertia_requests(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $route = $this->createTestRoute();
        $this->mock(BillingContract::class, function (MockInterface $mock) {
            $mock->shouldReceive('hasSubscription')->andReturn(false);
            $mock->shouldReceive('hasTrial')->andReturn(false);
            $mock->shouldReceive('isBlocked')->andReturn(false);
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('billing.has_subscription', false)
            ->where('billing.has_trial', false)
            ->where('billing.is_blocked', false)
        );
    }
}
