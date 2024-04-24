<?php

declare(strict_types=1);

namespace Tests;

use App\Service\PermissionStore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Mail;
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
}
