<?php

declare(strict_types=1);

namespace Tests;

use App\Service\BillableRateService;
use App\Service\PermissionStore;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use TiMacDonald\Log\LogFake;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        LogFake::bind();
    }

    protected function tearDown(): void
    {
        // Note: It is necessary to clear the permission cache after each test, since the "scoped singletons" are not reset between tests.
        app(PermissionStore::class)->clear();
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
        $this->mock(BillableRateService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('updateTimeEntriesBillableRateForProjectMember');
            $mock->shouldNotReceive('updateTimeEntriesBillableRateForProject');
            $mock->shouldNotReceive('updateTimeEntriesBillableRateForMember');
            $mock->shouldNotReceive('updateTimeEntriesBillableRateForOrganization');
        });
    }
}
