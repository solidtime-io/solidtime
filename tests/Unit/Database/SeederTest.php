<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class SeederTest extends \Tests\TestCase
{
    use RefreshDatabase;

    public function test_running_the_seeder_multiple_times_runs_successfully(): void
    {
        Artisan::call('db:seed');
        Artisan::call('db:seed');
        $this->assertTrue(true);
    }

    public function test_fresh_migration_with_seeder_and_rollback_runs_successfully(): void
    {
        Artisan::call('migrate:fresh --seed');
        Artisan::call('migrate:rollback');
        $this->assertTrue(true);
    }
}
