<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\HandleInertiaRequests;
use App\Service\BillingContract;
use App\Service\WhitelabelContract;
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
            $mock->shouldReceive('getGoalLimit')->andReturn(1);
            $mock->shouldReceive('canUseBillableRates')->andReturn(true);
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
            ->where('billing.goal_limit', 1)
            ->where('billing.can_use_billable_rates', true)
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
            $mock->shouldReceive('getGoalLimit')->andReturn(null);
            $mock->shouldReceive('canUseBillableRates')->andReturn(true);
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
            ->where('billing.goal_limit', null)
            ->where('billing.can_use_billable_rates', true)
        );
    }

    public function test_adds_default_whitelabel_information_to_shared_data_of_inertia_requests(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $route = $this->createTestRoute();
        Passport::actingAs($user->user);

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('whitelabel.name', 'solidtime')
            ->where('whitelabel.logo_url', null)
            ->where('whitelabel.dark_logo_url', null)
        );
    }

    public function test_adds_custom_whitelabel_information_to_shared_data_of_inertia_requests(): void
    {
        // Arrange
        $this->actAsWhitelabeledInstance();
        $route = $this->createTestRoute();

        // Act
        $response = $this->get($route);

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('whitelabel.name', 'Acme Time')
            ->where('whitelabel.logo_url', 'https://cdn.acme.test/logo.svg')
            ->where('whitelabel.dark_logo_url', 'https://cdn.acme.test/logo-dark.png')
        );
        $response->assertSee('<title inertia>Acme Time</title>', false);
        $response->assertSee('<link rel="icon" href="https://cdn.acme.test/favicon.png">', false);
        $response->assertDontSee('/favicons/favicon.ico', false);
        $response->assertSee('<meta name="theme-color" content="#12ab34">', false);
    }

    public function test_does_not_load_whitelabel_information_on_inertia_visits_that_already_have_it(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $route = $this->createTestRoute();
        $this->mock(WhitelabelContract::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('getName');
            $mock->shouldNotReceive('getLogoUrl');
            $mock->shouldNotReceive('getLogoSvgUrl');
            $mock->shouldNotReceive('getWebLogoUrl');
            $mock->shouldNotReceive('getDarkLogoUrl');
            $mock->shouldNotReceive('getFaviconUrl');
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
            'X-Inertia-Except-Once-Props' => 'whitelabel',
        ])->get($route);

        // Assert
        $response->assertOk();
        $response->assertJsonMissingPath('props.whitelabel');
    }

    public function test_does_not_load_whitelabel_information_on_api_requests(): void
    {
        // Arrange
        $user = $this->createUserWithPermission();
        $this->mock(WhitelabelContract::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('getName');
            $mock->shouldNotReceive('getLogoUrl');
            $mock->shouldNotReceive('getLogoSvgUrl');
            $mock->shouldNotReceive('getWebLogoUrl');
            $mock->shouldNotReceive('getDarkLogoUrl');
            $mock->shouldNotReceive('getFaviconUrl');
        });
        Passport::actingAs($user->user);

        // Act
        $response = $this->getJson(route('api.v1.users.me'));

        // Assert
        $response->assertOk();
    }
}
