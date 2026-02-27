<?php

declare(strict_types=1);

namespace Extensions\Linear\Services;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Carbon;

class LinearSyncService
{
    private const DEFAULT_PROJECT_COLOR = '#6B7280';

    /**
     * @param  array{id: string, title: string, state: array{type: string}, project: array{id: string, name: string}|null, estimate: float|null}  $issueData
     */
    public function upsertTask(Organization $organization, array $issueData): Task
    {
        if ($issueData['project'] !== null) {
            $project = $this->upsertProject($organization, $issueData['project']);
        } else {
            $project = $this->getOrCreateDefaultProject($organization);
        }

        $doneAt = in_array($issueData['state']['type'], ['completed', 'canceled'], true)
            ? Carbon::now()
            : null;

        $task = Task::where('linear_id', $issueData['id'])
            ->where('organization_id', $organization->getKey())
            ->first();

        if ($task !== null) {
            $task->update([
                'name' => $issueData['title'],
                'project_id' => $project->getKey(),
                'done_at' => $doneAt,
                'estimated_time' => $issueData['estimate'] !== null ? (int) ($issueData['estimate'] * 3600) : $task->estimated_time,
            ]);
        } else {
            $task = new Task();
            $task->linear_id = $issueData['id'];
            $task->name = $issueData['title'];
            $task->organization_id = $organization->getKey();
            $task->project_id = $project->getKey();
            $task->done_at = $doneAt;
            $task->estimated_time = $issueData['estimate'] !== null ? (int) ($issueData['estimate'] * 3600) : null;
            $task->save();
        }

        return $task;
    }

    /**
     * @param  array{id: string, name: string}  $projectData
     */
    private function upsertProject(Organization $organization, array $projectData): Project
    {
        $project = Project::where('linear_project_id', $projectData['id'])
            ->where('organization_id', $organization->getKey())
            ->first();

        if ($project !== null) {
            return $project;
        }

        $project = new Project();
        $project->linear_project_id = $projectData['id'];
        $project->name = $projectData['name'];
        $project->color = self::DEFAULT_PROJECT_COLOR;
        $project->organization_id = $organization->getKey();
        $project->is_billable = false;
        $project->save();

        return $project;
    }

    private function getOrCreateDefaultProject(Organization $organization): Project
    {
        $project = Project::where('name', 'Linear')
            ->where('organization_id', $organization->getKey())
            ->whereNull('linear_project_id')
            ->first();

        if ($project !== null) {
            return $project;
        }

        $project = new Project();
        $project->name = 'Linear';
        $project->color = self::DEFAULT_PROJECT_COLOR;
        $project->organization_id = $organization->getKey();
        $project->is_billable = false;
        $project->save();

        return $project;
    }
}
