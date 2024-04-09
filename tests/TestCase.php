<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use TiMacDonald\Log\LogFake;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        LogFake::bind();
    }

    protected function assertEqualsIdsOfEloquentCollection(array $ids, Collection $models): void
    {
        $this->assertEqualsCanonicalizing($ids, $models->pluck('id')->toArray());
    }
}
