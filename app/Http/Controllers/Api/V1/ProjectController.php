<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\EntityStillInUseApiException;
use App\Http\Requests\V1\Project\ProjectIndexRequest;
use App\Http\Requests\V1\Project\ProjectStoreRequest;
use App\Http\Requests\V1\Project\ProjectUpdateRequest;
use App\Http\Resources\V1\Project\ProjectCollection;
use App\Http\Resources\V1\Project\ProjectResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Project $project = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($project !== null && $project->organization_id !== $organization->id) {
            throw new AuthorizationException('Project does not belong to organization');
        }
    }

    /**
     * Get projects visible to the current user
     *
     * @return ProjectCollection<ProjectResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getProjects
     */
    public function index(Organization $organization, ProjectIndexRequest $request): ProjectCollection
    {
        $this->checkPermission($organization, 'projects:view');
        $canViewAllProjects = $this->hasPermission($organization, 'projects:view:all');
        $user = $this->user();

        $projectsQuery = Project::query()
            ->whereBelongsTo($organization, 'organization');

        if (! $canViewAllProjects) {
            $projectsQuery->visibleByEmployee($user);
        }

        $projects = $projectsQuery->paginate(config('app.pagination_per_page_default'));

        return new ProjectCollection($projects);
    }

    /**
     * Get project
     *
     * @throws AuthorizationException
     *
     * @operationId getProject
     */
    public function show(Organization $organization, Project $project): JsonResource
    {
        $this->checkPermission($organization, 'projects:view', $project);

        $project->load('organization');

        return new ProjectResource($project);
    }

    /**
     * Create project
     *
     * @throws AuthorizationException
     *
     * @operationId createProject
     */
    public function store(Organization $organization, ProjectStoreRequest $request): JsonResource
    {
        $this->checkPermission($organization, 'projects:create');
        $project = new Project();
        $project->name = $request->input('name');
        $project->color = $request->input('color');
        $project->is_billable = (bool) $request->input('is_billable');
        $project->billable_rate = $request->input('billable_rate');
        $project->client_id = $request->input('client_id');
        $project->organization()->associate($organization);
        $project->save();

        return new ProjectResource($project);
    }

    /**
     * Update project
     *
     * @throws AuthorizationException
     *
     * @operationId updateProject
     */
    public function update(Organization $organization, Project $project, ProjectUpdateRequest $request): JsonResource
    {
        $this->checkPermission($organization, 'projects:update', $project);
        $project->name = $request->input('name');
        $project->color = $request->input('color');
        $project->is_billable = (bool) $request->input('is_billable');
        $project->billable_rate = $request->input('billable_rate');
        $project->client_id = $request->input('client_id');
        $project->save();

        return new ProjectResource($project);
    }

    /**
     * Delete project
     *
     * @throws AuthorizationException|EntityStillInUseApiException
     *
     * @operationId deleteProject
     */
    public function destroy(Organization $organization, Project $project): JsonResponse
    {
        $this->checkPermission($organization, 'projects:delete', $project);

        if ($project->tasks()->exists()) {
            throw new EntityStillInUseApiException('project', 'task');
        }
        if ($project->timeEntries()->exists()) {
            throw new EntityStillInUseApiException('project', 'time_entry');
        }

        DB::transaction(function () use (&$project) {
            $project->members->each(function (ProjectMember $member) {
                $member->delete();
            });

            $project->delete();
        });

        return response()
            ->json(null, 204);
    }
}
