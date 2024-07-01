<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_the_seeder_multiple_times_runs_successfully(): void
    {
        $this->artisan('db:seed')
            ->assertSuccessful();
        $this->artisan('db:seed')
            ->assertSuccessful();
    }

    public function test_fresh_migration_with_seeder_and_rollback_runs_successfully(): void
    {
        $this->artisan('db:seed')
            ->assertSuccessful();
        $this->artisan('migrate:rollback')
            ->assertSuccessful();
    }
}
