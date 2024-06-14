<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Exceptions\Api\CanNotRemoveOwnerFromOrganization;
use App\Exceptions\Api\EntityStillInUseApiException;
use App\Exceptions\Api\UserNotPlaceholderApiException;
use App\Http\Requests\V1\Member\MemberIndexRequest;
use App\Http\Requests\V1\Member\MemberUpdateRequest;
use App\Http\Resources\V1\Member\MemberCollection;
use App\Http\Resources\V1\Member\MemberPivotResource;
use App\Http\Resources\V1\Member\MemberResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Service\BillableRateService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;

class MemberController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Member $member = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($member !== null && $member->organization_id !== $organization->id) {
            throw new AuthorizationException('Member does not belong to organization');
        }
    }

    /**
     * List all members of an organization
     *
     * @return MemberCollection<MemberPivotResource>>
     *
     * @throws AuthorizationException
     *
     * @operationId getMembers
     */
    public function index(Organization $organization, MemberIndexRequest $request): MemberCollection
    {
        $this->checkPermission($organization, 'members:view');

        $members = $organization->users()
            ->paginate(config('app.pagination_per_page_default'));

        return MemberCollection::make($members);
    }

    /**
     * Update a member of the organization
     *
     * @throws AuthorizationException
     *
     * @operationId updateMember
     */
    public function update(Organization $organization, Member $member, MemberUpdateRequest $request, BillableRateService $billableRateService): JsonResource
    {
        $this->checkPermission($organization, 'members:update', $member);

        if ($request->has('billable_rate')) {
            $member->billable_rate = $request->getBillableRate();
        }
        if ($request->has('role')) {
            $member->role = $request->input('role');
        }
        $member->save();

        if ($request->getBillableRateUpdateTimeEntries()) {
            $billableRateService->updateTimeEntriesBillableRateForMember($member);
        }

        return new MemberResource($member);
    }

    /**
     * Remove a member of the organization.
     *
     * @throws AuthorizationException|EntityStillInUseApiException|CanNotRemoveOwnerFromOrganization
     *
     * @operationId removeMember
     */
    public function destroy(Organization $organization, Member $member): JsonResponse
    {
        $this->checkPermission($organization, 'members:delete', $member);

        if (TimeEntry::query()->where('user_id', $member->user_id)->whereBelongsTo($organization, 'organization')->exists()) {
            throw new EntityStillInUseApiException('member', 'time_entry');
        }
        if (ProjectMember::query()->whereBelongsToOrganization($organization)->where('user_id', $member->user_id)->exists()) {
            throw new EntityStillInUseApiException('member', 'project_member');
        }
        if ($member->role === Role::Owner->value) {
            throw new CanNotRemoveOwnerFromOrganization();
        }

        $member->delete();

        return response()
            ->json(null, 204);
    }

    /**
     * Invite a placeholder member to become a real member of the organization
     *
     * @throws AuthorizationException|UserNotPlaceholderApiException
     *
     * @operationId invitePlaceholder
     */
    public function invitePlaceholder(Organization $organization, Member $member, Request $request): JsonResponse
    {
        $this->checkPermission($organization, 'members:invite-placeholder', $member);
        $user = $member->user;

        if (! $user->is_placeholder) {
            throw new UserNotPlaceholderApiException();
        }

        app(InvitesTeamMembers::class)->invite(
            $this->user(),
            $organization,
            $user->email,
            Role::Employee->value,
        );

        return response()->json(null, 204);
    }
}
