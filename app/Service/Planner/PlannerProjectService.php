<?php

declare(strict_types=1);

namespace App\Service\Planner;

use App\Models\MilestoneTemplate;
use App\Models\Organization;
use App\Models\PhaseMilestone;
use App\Models\PhaseTemplate;
use App\Models\Project;
use App\Models\ProjectPhase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PlannerProjectService
{
    /**
     * Materialize project phases and milestones from canonical templates.
     * Minimal implementation: clones PhaseTemplate and MilestoneTemplate into
     * ProjectPhase and PhaseMilestone for a given project.
     * Idempotency: if project_phases already exist for this project, do nothing.
     */
    public function materialize(Project $project): void
    {
        if (!config('planner.enabled')) {
            return;
        }
        if (ProjectPhase::query()->where('project_id', $project->getKey())->exists()) {
            // Already materialized.
            return;
        }

        /** @var Organization $organization */
        $organization = $project->organization;

        DB::transaction(function () use ($project, $organization): void {
            $phaseTemplates = PhaseTemplate::query()->orderBy('position')->get();
            foreach ($phaseTemplates as $phaseTemplate) {
                $projectPhase = new ProjectPhase();
                $projectPhase->project_id = $project->getKey();
                $projectPhase->phase_template_id = $phaseTemplate->getKey();
                $projectPhase->name = $phaseTemplate->name;
                $projectPhase->position = $phaseTemplate->position;
                $projectPhase->status = 'pending';
                $projectPhase->save();

                $milestoneTemplates = MilestoneTemplate::query()
                    ->where('phase_template_id', $phaseTemplate->getKey())
                    ->orderBy('position')
                    ->get();

                foreach ($milestoneTemplates as $mt) {
                    $pm = new PhaseMilestone();
                    $pm->project_phase_id = $projectPhase->getKey();
                    $pm->milestone_template_id = $mt->getKey();
                    $pm->name = $mt->name;
                    $pm->is_milestone = (bool) $mt->is_milestone;
                    $pm->position = $mt->position;
                    $pm->due_at = $mt->due_offset_days !== null ? Carbon::now()->addDays((int) $mt->due_offset_days) : null;
                    $pm->save();
                }
            }
        });
    }

    /**
     * Compute simple RAG status for a milestone using lead-time & alert windows.
     * - Green: due_at null or due_at > now + alert_window
     * - Amber: within alert window (now <= due_at <= now+alert)
     * - Red: overdue (due_at < now)
     */
    public function computeRag(?\DateTimeInterface $dueAt): string
    {
        if ($dueAt === null) {
            return 'green';
        }
        $alertDays = (int) config('planner.alert_window_days', 10);
        $now = Carbon::now();
        $alertEnd = $now->copy()->addDays($alertDays);
        if ($dueAt < $now) {
            return 'red';
        }
        if ($dueAt <= $alertEnd) {
            return 'amber';
        }
        return 'green';
    }
}
