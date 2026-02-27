<?php

declare(strict_types=1);

namespace Extensions\Linear\Services;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Carbon;

class LinearSyncService
{
    /**
     * @param  array{id: string, title: string, state: array{type: string}, project: array{id: string, name: string}|null, estimate: float|null}  $issueData
     */
    public function upsertTask(Organization $organization, array $issueData): Task
    {
        $projectId = null;
        if ($issueData['project'] !== null) {
            $project = $this->upsertProject($organization, $issueData['project']);
            $projectId = $project->getKey();
        }

        $doneAt = in_array($issueData['state']['type'], ['completed', 'canceled'], true)
            ? Carbon::now()
            : null;

        $task = Task::where('linear_id', $issueData['id'])
            ->where('organization_id', $organization->getKey())
            ->first();

        if ($task !== null) {
            $task->name = $issueData['title'];
            if ($projectId !== null) {
                $task->project_id = $projectId;
            }
            $task->done_at = $doneAt;
            $task->estimated_time = $issueData['estimate'] !== null
                ? (int) ($issueData['estimate'] * 3600)
                : $task->estimated_time;
            $task->save();
        } else {
            if ($projectId === null) {
                $defaultProject = $this->getOrCreateDefaultProject($organization);
                $projectId = $defaultProject->getKey();
            }

            $task = new Task();
            $task->linear_id = $issueData['id'];
            $task->name = $issueData['title'];
            $task->organization_id = $organization->getKey();
            $task->project_id = $projectId;
            $task->done_at = $doneAt;
            $task->estimated_time = $issueData['estimate'] !== null
                ? (int) ($issueData['estimate'] * 3600)
                : null;
            $task->save();
        }

        return $task;
    }

    private function getOrCreateDefaultProject(Organization $organization): Project
    {
        $project = Project::where('linear_project_id', 'linear-default')
            ->where('organization_id', $organization->getKey())
            ->first();

        if ($project !== null) {
            return $project;
        }

        $project = new Project();
        $project->linear_project_id = 'linear-default';
        $project->name = 'Linear';
        $project->color = '#5E6AD2';
        $project->organization_id = $organization->getKey();
        $project->is_billable = false;
        $project->save();

        return $project;
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
        $project->color = '#' . substr(md5($projectData['id']), 0, 6);
        $project->organization_id = $organization->getKey();
        $project->is_billable = false;
        $project->save();

        return $project;
    }
}
