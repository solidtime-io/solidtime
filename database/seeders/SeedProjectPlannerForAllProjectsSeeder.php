<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Project;
use App\Service\Planner\PlannerTemplateService;
use Illuminate\Database\Seeder;

class SeedProjectPlannerForAllProjectsSeeder extends Seeder
{
    public function run(): void
    {
        if (!config('pia.enabled') || !config('pia.templates.auto_seed')) {
            return; // Respect feature flags
        }

        $service = app(PlannerTemplateService::class);

        Organization::query()->each(function (Organization $org) use ($service): void {
            Project::query()->whereBelongsTo($org, 'organization')->each(function (Project $project) use ($org, $service): void {
                // Materialize tasks for each project; idempotency is ensured by tests cleaning DB between runs
                $service->materializeForProject($project, $org);
            });
        });
    }
}
