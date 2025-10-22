<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Models\Organization;
use App\Models\Project;
use Database\Seeders\SeedProjectPlannerForAllProjectsSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCaseWithDatabase;

class PlannerSeederTest extends TestCaseWithDatabase
{
    public function test_seeder_respects_feature_flags_disabled(): void
    {
        config(['pia.enabled' => false]);
        config(['pia.templates.auto_seed' => true]);

        $org = Organization::factory()->create();
        $project = Project::factory()->for($org, 'organization')->create();

        $this->assertSame(0, $project->tasks()->count());

        $this->seed(SeedProjectPlannerForAllProjectsSeeder::class);

        $this->assertSame(0, $project->fresh()->tasks()->count());
    }

    public function test_seeder_materializes_when_enabled(): void
    {
        config(['pia.enabled' => true]);
        config(['pia.templates.auto_seed' => true]);

        $org = Organization::factory()->create();
        $project = Project::factory()->for($org, 'organization')->create();

        $this->assertSame(0, $project->tasks()->count());

        $this->seed(SeedProjectPlannerForAllProjectsSeeder::class);

        $this->assertGreaterThan(0, $project->fresh()->tasks()->count());
    }
}
