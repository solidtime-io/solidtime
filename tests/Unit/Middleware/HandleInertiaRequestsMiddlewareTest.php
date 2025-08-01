<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\HandleInertiaRequests;
use App\Service\BillingContract;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HandleInertiaRequests::class)]
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
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('hasSubscription')->andReturn(false);
            $mock->shouldReceive('hasTrial')->andReturn(false);
            $mock->shouldReceive('getTrialUntil')->andReturn(null);
            $mock->shouldReceive('isBlocked')->andReturn(false);
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('billing.has_subscription', false)
            ->where('billing.has_trial', false)
            ->where('billing.trial_until', null)
            ->where('billing.is_blocked', false)
        );
    }

    public function test_adds_billing_information_to_shared_data_of_inertia_requests_with_active_trial(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $route = $this->createTestRoute();
        $trialUntil = Carbon::now()->addDays(10);
        $this->mock(BillingContract::class, function (MockInterface $mock) use ($trialUntil): void {
            $mock->shouldReceive('hasSubscription')->andReturn(false);
            $mock->shouldReceive('hasTrial')->andReturn(true);
            $mock->shouldReceive('getTrialUntil')->andReturn($trialUntil);
            $mock->shouldReceive('isBlocked')->andReturn(false);
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('billing.has_subscription', false)
            ->where('billing.has_trial', true)
            ->where('billing.trial_until', $trialUntil->toIso8601ZuluString())
            ->where('billing.is_blocked', false)
        );
    }
}
