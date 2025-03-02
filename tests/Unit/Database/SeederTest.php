<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_the_seeder_multiple_times_runs_successfully(): void
    {
        $this->setupForSeeder();
        $this->artisan('db:seed')
            ->assertSuccessful();
        $this->artisan('db:seed')
            ->assertSuccessful();
    }

    public function test_fresh_migration_with_seeder_and_rollback_runs_successfully(): void
    {
        $this->setupForSeeder();
        $this->artisan('db:seed')
            ->assertSuccessful();
        $this->artisan('migrate:rollback')
            ->assertSuccessful();
    }

    private function setupForSeeder(): void
    {
        Config::set('passport.personal_access_client.id', '9e27f54d-5dfb-4dde-99d7-834518236c92');
        Config::set('passport.personal_access_client.secret', 'EL5mXp3aF8ITjcwoOXRpbSK7zGrWhW4zTDpQXTkf');
    }
}
