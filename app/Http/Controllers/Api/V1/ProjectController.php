<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectMemberRole;
use App\Exceptions\Api\EntityStillInUseApiException;
use App\Http\Requests\V1\Project\ProjectIndexRequest;
use App\Http\Requests\V1\Project\ProjectStoreRequest;
use App\Http\Requests\V1\Project\ProjectUpdateRequest;
use App\Http\Resources\V1\Project\ProjectCollection;
use App\Http\Resources\V1\Project\ProjectResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Service\BillableRateService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
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
            $projectsQuery->with([
                'members' => function (HasMany $query): void {
                    /** @var Builder<ProjectMember> $query */
                    $query->whereBelongsTo($this->user(), 'user');
                },
            ]);
        }
        $filterArchived = $request->getFilterArchived();
        if ($filterArchived === 'true') {
            $projectsQuery->whereNotNull('archived_at');
        } elseif ($filterArchived === 'false') {
            $projectsQuery->whereNull('archived_at');
        }

        $projects = $projectsQuery->paginate(config('app.pagination_per_page_default'));

        foreach ($projects->items() as $project) {
            if ($canViewAllProjects) {
                $project->setAttribute('limited_visibility', false);
            } else {
                $project->setAttribute('limited_visibility', $project->members->firstWhere('user_id', $this->user()->id)?->role !== ProjectMemberRole::Manager);
            }
        }

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
        $canViewAllProjects = $this->hasPermission($organization, 'projects:view:all');

        $project->load([
            'members' => function (HasMany $query): void {
                /** @var Builder<ProjectMember> $query */
                $query->whereBelongsTo($this->user(), 'user');
            },
        ]);

        if (! $canViewAllProjects) {
            if (! $project->is_public && $project->members->firstWhere('user_id', '=', $this->user()->id) === null) {
                throw new AuthorizationException('No access to project');
            }
        }

        if ($canViewAllProjects) {
            $project->setAttribute('limited_visibility', false);
        } else {
            $project->setAttribute('limited_visibility', $project->members->firstWhere('user_id', $this->user()->id)?->role !== ProjectMemberRole::Manager);
        }

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
        $project = new Project;
        $project->name = $request->input('name');
        $project->color = $request->input('color');
        $project->is_billable = (bool) $request->input('is_billable');
        $project->billable_rate = $request->getBillableRate();
        $project->client_id = $request->input('client_id');
        if ($this->canAccessPremiumFeatures($organization) && $request->has('estimated_time')) {
            $project->estimated_time = $request->getEstimatedTime();
        }
        $project->organization()->associate($organization);
        $project->save();

        $project->setAttribute('limited_visibility', false);

        return new ProjectResource($project);
    }

    /**
     * Update project
     *
     * @throws AuthorizationException
     *
     * @operationId updateProject
     */
    public function update(Organization $organization, Project $project, ProjectUpdateRequest $request, BillableRateService $billableRateService): JsonResource
    {
        $this->checkPermission($organization, 'projects:update', $project);
        $project->name = $request->input('name');
        $project->color = $request->input('color');
        $project->is_billable = (bool) $request->input('is_billable');
        if ($request->has('is_archived')) {
            $project->archived_at = $request->getIsArchived() ? Carbon::now() : null;
        }
        if ($this->canAccessPremiumFeatures($organization) && $request->has('estimated_time')) {
            $project->estimated_time = $request->getEstimatedTime();
        }
        $oldBillableRate = $project->billable_rate;
        $project->billable_rate = $request->getBillableRate();
        $project->client_id = $request->input('client_id');
        $project->save();

        if ($oldBillableRate !== $request->getBillableRate()) {
            $billableRateService->updateTimeEntriesBillableRateForProject($project);
        }

        $project->setAttribute('limited_visibility', false);

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

        DB::transaction(function () use (&$project): void {
            $project->members->each(function (ProjectMember $member): void {
                $member->delete();
            });

            $project->delete();
        });

        return response()
            ->json(null, 204);
    }
}
