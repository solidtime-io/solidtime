<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMilestoneTemplate;
use App\Models\ProjectPhaseTemplate;
use App\Service\Planner\PlannerTemplateService;
use Illuminate\Support\Arr;
use Tests\TestCaseWithDatabase;

class PlannerTemplateServiceTest extends TestCaseWithDatabase
{
    public function test_materialize_uses_config_defaults_when_no_db_templates(): void
    {
        config(['pia.enabled' => true]);
        config(['pia.templates.auto_seed' => true]);

        // Ensure no DB templates exist
        $this->assertSame(0, ProjectPhaseTemplate::query()->count());
        $this->assertSame(0, ProjectMilestoneTemplate::query()->count());

        $org = Organization::factory()->create();
        $project = Project::factory()->for($org, 'organization')->create();

        /** @var PlannerTemplateService $svc */
        $svc = app(PlannerTemplateService::class);
        $svc->materializeForProject($project, $org);

        $defaults = (array) config('pia.templates.defaults', []);
        $expectedCount = 0;
        foreach ($defaults as $phase) {
            $expectedCount += count((array) Arr::get($phase, 'milestones', []));
        }

        $this->assertSame($expectedCount, $project->tasks()->count());
    }

    public function test_materialize_uses_db_templates_when_present(): void
    {
        config(['pia.enabled' => true]);
        config(['pia.templates.auto_seed' => true]);

        // Seed one phase with three milestones
        $phase = new ProjectPhaseTemplate();
        $phase->name = 'Phase A';
        $phase->position = 1;
        $phase->save();

        foreach (['M1', 'M2', 'M3'] as $idx => $name) {
            $mt = new ProjectMilestoneTemplate();
            $mt->project_phase_template_id = $phase->id;
            $mt->name = $name;
            $mt->is_milestone = true;
            $mt->position = $idx + 1;
            $mt->save();
        }

        $org = Organization::factory()->create();
        $project = Project::factory()->for($org, 'organization')->create();

        /** @var PlannerTemplateService $svc */
        $svc = app(PlannerTemplateService::class);
        $svc->materializeForProject($project, $org);

        $this->assertSame(3, $project->tasks()->count());
        $this->assertEqualsCanonicalizing(['M1','M2','M3'], $project->tasks()->pluck('name')->all());
        $this->assertTrue($project->tasks()->where('is_milestone', true)->count() === 3);
    }
}
