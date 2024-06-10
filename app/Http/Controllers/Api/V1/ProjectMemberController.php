<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\InactiveUserCanNotBeUsedApiException;
use App\Exceptions\Api\UserIsAlreadyMemberOfProjectApiException;
use App\Http\Requests\V1\ProjectMember\ProjectMemberStoreRequest;
use App\Http\Requests\V1\ProjectMember\ProjectMemberUpdateRequest;
use App\Http\Resources\V1\ProjectMember\ProjectMemberCollection;
use App\Http\Resources\V1\ProjectMember\ProjectMemberResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectMemberController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Project $project = null, ?ProjectMember $projectMember = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($project !== null && $project->organization_id !== $organization->id) {
            throw new AuthorizationException('Project does not belong to organization');
        }
        if ($projectMember !== null && $projectMember->project->organization_id !== $organization->id) {
            throw new AuthorizationException('Project member does not belong to organization');
        }
    }

    /**
     * Get project members for project
     *
     * @return ProjectMemberCollection<ProjectMemberResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getProjectMembers
     */
    public function index(Organization $organization, Project $project): ProjectMemberCollection
    {
        $this->checkPermission($organization, 'project-members:view', $project);

        $projectMembers = ProjectMember::query()
            ->whereBelongsTo($project, 'project')
            ->paginate(config('app.pagination_per_page_default'));

        return new ProjectMemberCollection($projectMembers);
    }

    /**
     * Add project member to project
     *
     * @throws AuthorizationException|InactiveUserCanNotBeUsedApiException|UserIsAlreadyMemberOfProjectApiException
     *
     * @operationId createProjectMember
     */
    public function store(Organization $organization, Project $project, ProjectMemberStoreRequest $request): JsonResource
    {
        $this->checkPermission($organization, 'project-members:create', $project);

        $member = Member::findOrFail((string) $request->input('member_id'));
        if ($member->user->is_placeholder) {
            throw new InactiveUserCanNotBeUsedApiException();
        }
        if (ProjectMember::whereBelongsTo($project, 'project')->whereBelongsTo($member, 'member')->exists()) {
            throw new UserIsAlreadyMemberOfProjectApiException();
        }

        $projectMember = new ProjectMember();
        $projectMember->billable_rate = $request->getBillableRate();
        $projectMember->member()->associate($member);
        $projectMember->user()->associate($member->user);
        $projectMember->project()->associate($project);
        $projectMember->save();

        return new ProjectMemberResource($projectMember);
    }

    /**
     * Update project member
     *
     * @throws AuthorizationException
     *
     * @operationId updateProjectMember
     */
    public function update(Organization $organization, ProjectMember $projectMember, ProjectMemberUpdateRequest $request): JsonResource
    {
        $this->checkPermission($organization, 'project-members:update', projectMember: $projectMember);
        $projectMember->billable_rate = $request->getBillableRate();
        $projectMember->save();

        return new ProjectMemberResource($projectMember);
    }

    /**
     * Delete project member
     *
     * @throws AuthorizationException
     *
     * @operationId deleteProjectMember
     */
    public function destroy(Organization $organization, ProjectMember $projectMember): JsonResponse
    {
        $this->checkPermission($organization, 'project-members:delete', projectMember: $projectMember);

        $projectMember->delete();

        return response()
            ->json(null, 204);
    }
}
