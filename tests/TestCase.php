<?php

declare(strict_types=1);

namespace Tests;

use App\Service\AuthenticationContract;
use App\Service\BillableRateService;
use App\Service\BillingContract;
use App\Service\PermissionStore;
use App\Service\WhitelabelContract;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use TiMacDonald\Log\LogFake;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected bool $mockBillingContract = true;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        LogFake::bind();
        Http::preventStrayRequests();
        if ($this->mockBillingContract) {
            $this->actAsOrganizationWithoutSubscriptionAndWithoutTrial();
        }
        // Core tests use the default branding, independent of an installed whitelabel extension and its environment variables
        $this->app->instance(WhitelabelContract::class, new WhitelabelContract);
        // Note: The following line can be used to test timezone edge cases.
        // $this->travelTo(Carbon::now()->timezone('Europe/Vienna')->setHour(0)->setMinute(59)->setSecond(0));
    }

    /**
     * Act as if an extension, like SSO, manages the email address of every user.
     */
    protected function lockEmailChanges(): void
    {
        $this->partialMock(AuthenticationContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('canChangeEmail')->andReturn(false);
        });
    }

    protected function mockPrivateStorage(): void
    {
        Storage::fake(config('filesystems.private'));
    }

    protected function mockPublicStorage(): void
    {
        Storage::fake(config('filesystems.public'));
    }

    protected function tearDown(): void
    {
        // Note: It is necessary to clear the permission cache after each test, since the "scoped singletons" are not reset between tests.
        app(PermissionStore::class)->clear();
        PermissionStore::resetCustomRoles();
        parent::tearDown();
    }

    protected function assertEqualsIdsOfEloquentCollection(array $ids, Collection $models): void
    {
        $this->assertEqualsCanonicalizing($ids, $models->pluck('id')->toArray());
    }

    /**
     * Set the current time to the given time.
     * This method fixes a bug, that setting the test now with Carbon::setTestNow() with a Carbon instance that has a timezone set, will not work as expected.
     * IT will also set the timezone for model casts with type "datetime" to the timezone and not use the timezone configured in the configuration "app.timezone".
     *
     * @param  Carbon|CarbonImmutable  $date
     * @param  callable|null  $callback
     */
    public function travelTo($date, $callback = null): void
    {
        parent::travelTo($date->utc());
    }

    protected function assertBillableRateServiceIsUnused(): void
    {
        $this->mock(BillableRateService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('updateTimeEntriesBillableRateForProjectMember');
            $mock->shouldNotReceive('updateTimeEntriesBillableRateForProject');
            $mock->shouldNotReceive('updateTimeEntriesBillableRateForMember');
            $mock->shouldNotReceive('updateTimeEntriesBillableRateForOrganization');
        });
    }

    protected function actAsOrganizationWithSubscription(): void
    {
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('hasSubscription')->andReturn(true);
            $mock->shouldReceive('hasTrial')->andReturn(false);
            $mock->shouldReceive('getTrialUntil')->andReturn(null);
            $mock->shouldReceive('getGoalLimit')->andReturn(null);
            $mock->shouldReceive('isBlocked')->andReturn(false);
            $mock->shouldReceive('canUseBillableRates')->andReturn(true);
        });
    }

    protected function actAsOrganizationWithoutSubscriptionAndWithoutTrial(): void
    {
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('hasSubscription')->andReturn(false);
            $mock->shouldReceive('hasTrial')->andReturn(false);
            $mock->shouldReceive('getTrialUntil')->andReturn(null);
            // Core has no goal limit, the limit of the free plan is implemented in the billing extension
            $mock->shouldReceive('getGoalLimit')->andReturn(null);
            $mock->shouldReceive('isBlocked')->andReturn(false);
            $mock->shouldReceive('canUseBillableRates')->andReturn(true);
        });
    }

    protected function actAsOrganizationWithoutBillableRates(): void
    {
        $this->mock(BillingContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('hasSubscription')->andReturn(false);
            $mock->shouldReceive('hasTrial')->andReturn(false);
            $mock->shouldReceive('getTrialUntil')->andReturn(null);
            $mock->shouldReceive('isBlocked')->andReturn(false);
            $mock->shouldReceive('canUseBillableRates')->andReturn(false);
        });
    }

    protected function actAsWhitelabeledInstance(): void
    {
        $this->mock(WhitelabelContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getName')->andReturn('Acme Time');
            $mock->shouldReceive('getLogoUrl')->andReturn('https://cdn.acme.test/logo.png');
            $mock->shouldReceive('getLogoSvgUrl')->andReturn('https://cdn.acme.test/logo.svg');
            $mock->shouldReceive('getWebLogoUrl')->andReturn('https://cdn.acme.test/logo.svg');
            $mock->shouldReceive('getDarkLogoUrl')->andReturn('https://cdn.acme.test/logo-dark.png');
            $mock->shouldReceive('getFaviconUrl')->andReturn('https://cdn.acme.test/favicon.png');
            $mock->shouldReceive('getFaviconPath')->andReturn(null);
            $mock->shouldReceive('getThemeColor')->andReturn('#12ab34');
        });
    }
}
