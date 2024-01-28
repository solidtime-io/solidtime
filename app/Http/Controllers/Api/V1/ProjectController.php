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
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(Organization $organization): JsonResource
    {
        $this->checkPermission($organization, 'projects:view');
        $projects = Project::query()
            ->whereBelongsTo($organization, 'organization')
            ->get();

        return new ProjectCollection($projects);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, Project $project): JsonResource
    {
        $this->checkPermission($organization, 'projects:view');
        $project->load('organization');

        return new ProjectResource($project);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(Organization $organization, ProjectStoreRequest $request): JsonResource
    {
        $this->checkPermission($organization, 'projects:create');
        $project = new Project();
        $project->name = $request->input('name');
        $project->color = $request->input('color');
        $project->organization()->associate($organization);
        $project->save();

        return new ProjectResource($project);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Organization $organization, Project $project, ProjectUpdateRequest $request): JsonResource
    {
        $this->checkPermission($organization, 'projects:update');
        $project->name = $request->input('name');
        $project->color = $request->input('color');
        $project->save();

        return new ProjectResource($project);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, Project $project): JsonResource
    {
        $this->checkPermission($organization, 'projects:delete');
        $project->delete();

        return new ProjectResource($project);
    }
}
