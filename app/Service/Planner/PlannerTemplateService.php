<?php

declare(strict_types=1);

namespace App\Service\Planner;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMilestoneTemplate;
use App\Models\ProjectPhaseTemplate;
use App\Models\Task;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlannerTemplateService
{
    /**
     * Create project tasks from templates.
     * - If DB templates exist, use them (ordered by position).
     * - Otherwise, fall back to config('pia.templates.defaults').
     */
    public function materializeForProject(Project $project, Organization $organization): void
    {
        $phaseTemplates = ProjectPhaseTemplate::query()
            ->orderBy('position')
            ->get();

        if ($phaseTemplates->isEmpty()) {
            $this->materializeFromConfig($project, $organization);
            return;
        }

        DB::transaction(function () use ($phaseTemplates, $project, $organization): void {
            foreach ($phaseTemplates as $phase) {
                /** @var Collection<int, ProjectMilestoneTemplate> $milestones */
                $milestones = $phase->milestones()->orderBy('position')->get();
                foreach ($milestones as $m) {
                    $task = new Task();
                    $task->name = $m->name; // keep names clean; phase can be implied by order
                    $task->project()->associate($project);
                    $task->organization()->associate($organization);
                    $task->is_milestone = $m->is_milestone;
                    if ($m->due_offset_days !== null) {
                        $task->due_at = Carbon::now()->addDays($m->due_offset_days);
                    }
                    $task->save();
                }
            }
        });
    }

    protected function materializeFromConfig(Project $project, Organization $organization): void
    {
        $defaults = (array) config('pia.templates.defaults', []);
        DB::transaction(function () use ($defaults, $project, $organization): void {
            foreach ($defaults as $phase) {
                $milestones = (array) Arr::get($phase, 'milestones', []);
                foreach ($milestones as $m) {
                    $task = new Task();
                    $task->name = (string) Arr::get($m, 'name');
                    $task->project()->associate($project);
                    $task->organization()->associate($organization);
                    $task->is_milestone = (bool) Arr::get($m, 'is_milestone', true);
                    $offset = Arr::get($m, 'due_offset_days');
                    if ($offset !== null) {
                        $task->due_at = Carbon::now()->addDays((int) $offset);
                    }
                    $task->save();
                }
            }
        });
    }
}
