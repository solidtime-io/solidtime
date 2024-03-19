<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Project\ProjectStoreRequest;
use App\Http\Requests\V1\Project\ProjectUpdateRequest;
use App\Http\Resources\V1\Project\ProjectCollection;
use App\Http\Resources\V1\Project\ProjectResource;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

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
     * Get projects
     *
     * @return ProjectCollection<ProjectResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getProjects
     */
    public function index(Organization $organization): ProjectCollection
    {
        $this->checkPermission($organization, 'projects:view');
        $projects = Project::query()
            ->whereBelongsTo($organization, 'organization')
            ->paginate();

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
        $project->save();

        return new ProjectResource($project);
    }

    /**
     * Delete project
     *
     * @throws AuthorizationException
     *
     * @operationId deleteProject
     */
    public function destroy(Organization $organization, Project $project): JsonResponse
    {
        $this->checkPermission($organization, 'projects:delete', $project);

        $project->delete();

        return response()
            ->json(null, 204);
    }
}
