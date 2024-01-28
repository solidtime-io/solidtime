<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class MigrationTest extends \Tests\TestCase
{
    use RefreshDatabase;

    public function test_fresh_migration_and_rollback_runs_successfully(): void
    {
        Artisan::call('migrate:fresh');
        Artisan::call('migrate:rollback');
        $this->assertTrue(true);
    }

    public function testFreshMigrationWithSeederAndRollbackRunsSuccessfully(): void
    {
        Artisan::call('migrate:fresh --seed');
        Artisan::call('migrate:rollback');
        $this->assertTrue(true);
    }
}
