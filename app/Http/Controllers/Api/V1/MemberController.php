<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Events\MemberMadeToPlaceholder;
use App\Events\MemberRemoved;
use App\Exceptions\Api\CanNotRemoveOwnerFromOrganization;
use App\Exceptions\Api\ChangingRoleToPlaceholderIsNotAllowed;
use App\Exceptions\Api\EntityStillInUseApiException;
use App\Exceptions\Api\OnlyOwnerCanChangeOwnership;
use App\Exceptions\Api\OrganizationNeedsAtLeastOneOwner;
use App\Exceptions\Api\UserNotPlaceholderApiException;
use App\Http\Requests\V1\Member\MemberIndexRequest;
use App\Http\Requests\V1\Member\MemberUpdateRequest;
use App\Http\Resources\V1\Member\MemberCollection;
use App\Http\Resources\V1\Member\MemberResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Service\BillableRateService;
use App\Service\InvitationService;
use App\Service\MemberService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

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
     * @return MemberCollection<MemberResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getMembers
     */
    public function index(Organization $organization, MemberIndexRequest $request): MemberCollection
    {
        $this->checkPermission($organization, 'members:view');

        $members = Member::query()
            ->whereBelongsTo($organization, 'organization')
            ->with(['user'])
            ->paginate(config('app.pagination_per_page_default'));

        return MemberCollection::make($members);
    }

    /**
     * Update a member of the organization
     *
     * @throws AuthorizationException
     * @throws OrganizationNeedsAtLeastOneOwner
     * @throws OnlyOwnerCanChangeOwnership
     * @throws ChangingRoleToPlaceholderIsNotAllowed
     *
     * @operationId updateMember
     */
    public function update(Organization $organization, Member $member, MemberUpdateRequest $request, BillableRateService $billableRateService, MemberService $memberService): JsonResource
    {
        $this->checkPermission($organization, 'members:update', $member);

        if ($request->has('billable_rate') && $member->billable_rate !== $request->getBillableRate()) {
            $member->billable_rate = $request->getBillableRate();

            $billableRateService->updateTimeEntriesBillableRateForMember($member);
        }
        if ($request->has('role') && $member->role !== $request->getRole()->value) {
            $newRole = $request->getRole();
            $oldRole = Role::from($member->role);
            if ($oldRole === Role::Owner) {
                throw new OrganizationNeedsAtLeastOneOwner();
            }
            if ($newRole === Role::Placeholder) {
                throw new ChangingRoleToPlaceholderIsNotAllowed();
            }
            if ($newRole === Role::Owner) {
                if ($this->hasPermission($organization, 'members:change-ownership')) {
                    $memberService->changeOwnership($organization, $member);
                } else {
                    throw new OnlyOwnerCanChangeOwnership();
                }
            } else {
                $member->role = $request->getRole()->value;
            }
        }
        $member->save();

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
        MemberRemoved::dispatch($member, $organization);

        return response()
            ->json(null, 204);
    }

    /**
     * @throws AuthorizationException|CanNotRemoveOwnerFromOrganization
     */
    public function makePlaceholder(Organization $organization, Member $member, MemberService $memberService): JsonResponse
    {
        $this->checkPermission($organization, 'members:make-placeholder', $member);

        if ($member->role === Role::Owner->value) {
            throw new CanNotRemoveOwnerFromOrganization();
        }

        $memberService->makeMemberToPlaceholder($member);

        MemberMadeToPlaceholder::dispatch($member, $organization);

        return response()->json(null, 204);
    }

    /**
     * Invite a placeholder member to become a real member of the organization
     *
     * @throws AuthorizationException|UserNotPlaceholderApiException
     *
     * @operationId invitePlaceholder
     */
    public function invitePlaceholder(Organization $organization, Member $member, InvitationService $invitationService): JsonResponse
    {
        $this->checkPermission($organization, 'members:invite-placeholder', $member);
        $user = $member->user;

        if (! $user->is_placeholder) {
            throw new UserNotPlaceholderApiException();
        }

        $invitationService->inviteUser($organization, $user->email, Role::Employee);

        return response()->json(null, 204);
    }
}
