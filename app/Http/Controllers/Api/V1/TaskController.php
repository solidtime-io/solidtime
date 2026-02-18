<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\EntityStillInUseApiException;
use App\Http\Requests\V1\Task\TaskIndexRequest;
use App\Http\Requests\V1\Task\TaskStoreRequest;
use App\Http\Requests\V1\Task\TaskUpdateRequest;
use App\Http\Resources\V1\Task\TaskCollection;
use App\Http\Resources\V1\Task\TaskResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Task $task = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($task !== null && $task->organization_id !== $organization->id) {
            throw new AuthorizationException('Task does not belong to organization');
        }
    }

    /**
     * Check scoped permission and verify user has access to the project
     *
     * @throws AuthorizationException
     */
    private function checkScopedPermissionForProject(Organization $organization, Project $project, string $permission): void
    {
        $this->checkPermission($organization, $permission);

        $user = $this->user();
        $hasAccess = Project::query()
            ->where('id', $project->id)
            ->visibleByEmployee($user)
            ->exists();

        if (! $hasAccess) {
            throw new AuthorizationException('You do not have permission to '.$permission.' in this project.');
        }
    }

    /**
     * Get tasks
     *
     * @return TaskCollection<TaskResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getTasks
     */
    public function index(Organization $organization, TaskIndexRequest $request): TaskCollection
    {
        $this->checkPermission($organization, 'tasks:view');
        $canViewAllTasks = $this->hasPermission($organization, 'tasks:view:all');
        $user = $this->user();

        $projectId = $request->input('project_id');

        $query = Task::query()
            ->whereBelongsTo($organization, 'organization');

        if ($projectId !== null) {
            $query->where('project_id', '=', $projectId);
        }

        if (! $canViewAllTasks) {
            $query->visibleByEmployee($user);
        }
        $doneFilter = $request->getFilterDone();
        if ($doneFilter === 'true') {
            $query->whereNotNull('done_at');
        } elseif ($doneFilter === 'false') {
            $query->whereNull('done_at');
        }

        $tasks = $query
            ->orderBy('created_at', 'desc')
            ->paginate(config('app.pagination_per_page_default'));

        return new TaskCollection($tasks);
    }

    /**
     * Create task
     *
     * @throws AuthorizationException
     *
     * @operationId createTask
     */
    public function store(Organization $organization, TaskStoreRequest $request): JsonResource
    {
        /** @var Project $project */
        $project = Project::query()->findOrFail($request->input('project_id'));

        if ($this->hasPermission($organization, 'tasks:create:all')) {
            $this->checkPermission($organization, 'tasks:create:all');
        } else {
            $this->checkScopedPermissionForProject($organization, $project, 'tasks:create');
        }

        $task = new Task;
        $task->name = $request->input('name');
        $task->project_id = $request->input('project_id');
        if ($this->canAccessPremiumFeatures($organization) && $request->has('estimated_time')) {
            $task->estimated_time = $request->getEstimatedTime();
        }
        $task->organization()->associate($organization);
        $task->save();

        return new TaskResource($task);
    }

    /**
     * Update task
     *
     * @throws AuthorizationException
     *
     * @operationId updateTask
     */
    public function update(Organization $organization, Task $task, TaskUpdateRequest $request): JsonResource
    {
        // Check task belongs to organization
        if ($task->organization_id !== $organization->id) {
            throw new AuthorizationException('Task does not belong to organization');
        }

        if ($this->hasPermission($organization, 'tasks:update:all')) {
            $this->checkPermission($organization, 'tasks:update:all');
        } else {
            $this->checkScopedPermissionForProject($organization, $task->project, 'tasks:update');
        }

        $task->name = $request->input('name');
        if ($this->canAccessPremiumFeatures($organization) && $request->has('estimated_time')) {
            $task->estimated_time = $request->getEstimatedTime();
        }
        if ($request->has('is_done')) {
            $task->done_at = $request->getIsDone() ? Carbon::now() : null;
        }
        $task->save();

        return new TaskResource($task);
    }

    /**
     * Delete task
     *
     * @throws AuthorizationException|EntityStillInUseApiException
     *
     * @operationId deleteTask
     */
    public function destroy(Organization $organization, Task $task): JsonResponse
    {
        // Check task belongs to organization
        if ($task->organization_id !== $organization->id) {
            throw new AuthorizationException('Task does not belong to organization');
        }

        if ($this->hasPermission($organization, 'tasks:delete:all')) {
            $this->checkPermission($organization, 'tasks:delete:all');
        } else {
            $this->checkScopedPermissionForProject($organization, $task->project, 'tasks:delete');
        }

        if ($task->timeEntries()->exists()) {
            throw new EntityStillInUseApiException('task', 'time_entry');
        }

        $task->delete();

        return response()
            ->json(null, 204);
    }
}
