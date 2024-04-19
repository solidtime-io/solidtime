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
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

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
        /** @var User $user */
        $user = Auth::user();

        $projectId = $request->input('project_id');

        $query = Task::query()
            ->whereBelongsTo($organization, 'organization');

        if ($projectId !== null) {
            $query->where('project_id', '=', $projectId);
        }

        if (! $canViewAllTasks) {
            $query->whereHas('project', function (Builder $builder) use ($user): void {
                /** @var Builder<Project> $builder */
                $builder->visibleByUser($user);
            });
        }

        $tasks = $query->paginate();

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
        $this->checkPermission($organization, 'tasks:create');
        $task = new Task();
        $task->name = $request->input('name');
        $task->project_id = $request->input('project_id');
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
        $this->checkPermission($organization, 'tasks:update', $task);
        $task->name = $request->input('name');
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
        $this->checkPermission($organization, 'tasks:delete', $task);

        if ($task->timeEntries()->exists()) {
            throw new EntityStillInUseApiException('task', 'time_entry');
        }

        $task->delete();

        return response()
            ->json(null, 204);
    }
}
