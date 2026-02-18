<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Events\MemberMadeToPlaceholder;
use App\Exceptions\Api\CanNotRemoveOwnerFromOrganization;
use App\Exceptions\Api\ChangingRoleOfPlaceholderIsNotAllowed;
use App\Exceptions\Api\ChangingRoleToPlaceholderIsNotAllowed;
use App\Exceptions\Api\EntityStillInUseApiException;
use App\Exceptions\Api\InvitationForTheEmailAlreadyExistsApiException;
use App\Exceptions\Api\OnlyOwnerCanChangeOwnership;
use App\Exceptions\Api\OnlyPlaceholdersCanBeMergedIntoAnotherMember;
use App\Exceptions\Api\OrganizationNeedsAtLeastOneOwner;
use App\Exceptions\Api\ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException;
use App\Exceptions\Api\UserIsAlreadyMemberOfOrganizationApiException;
use App\Exceptions\Api\UserNotPlaceholderApiException;
use App\Http\Requests\V1\Member\MemberDestroyRequest;
use App\Http\Requests\V1\Member\MemberIndexRequest;
use App\Http\Requests\V1\Member\MemberMergeIntoRequest;
use App\Http\Requests\V1\Member\MemberUpdateRequest;
use App\Http\Resources\V1\Member\MemberCollection;
use App\Http\Resources\V1\Member\MemberResource;
use App\Models\Member;
use App\Models\Organization;
use App\Service\BillableRateService;
use App\Service\InvitationService;
use App\Service\MemberService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            ->orderBy('created_at', 'desc')
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
     * @throws ChangingRoleOfPlaceholderIsNotAllowed
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
            $allowOwnerChange = $this->hasPermission($organization, 'members:change-ownership');
            $memberService->changeRole($member, $organization, $newRole, $allowOwnerChange);
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
    public function destroy(MemberDestroyRequest $request, Organization $organization, Member $member, MemberService $memberService): JsonResponse
    {
        $this->checkPermission($organization, 'members:delete', $member);

        $deleteRelated = $request->getDeleteRelated();

        $memberService->removeMember($member, $organization, $deleteRelated);

        return response()
            ->json(null, 204);
    }

    /**
     * Make a member a placeholder member
     *
     * @throws AuthorizationException|CanNotRemoveOwnerFromOrganization|ChangingRoleOfPlaceholderIsNotAllowed
     *
     * @operationId makePlaceholder
     */
    public function makePlaceholder(Organization $organization, Member $member, MemberService $memberService): JsonResponse
    {
        $this->checkPermission($organization, 'members:make-placeholder', $member);

        if ($member->role === Role::Owner->value) {
            throw new CanNotRemoveOwnerFromOrganization;
        }
        if ($member->role === Role::Placeholder->value) {
            throw new ChangingRoleOfPlaceholderIsNotAllowed;
        }

        $memberService->makeMemberToPlaceholder($member);

        MemberMadeToPlaceholder::dispatch($member, $organization);

        return response()->json(null, 204);
    }

    /**
     * Merge one member into another
     *
     * @throws AuthorizationException
     * @throws OnlyPlaceholdersCanBeMergedIntoAnotherMember
     * @throws \Throwable
     *
     * @operationId mergeMember
     */
    public function mergeInto(Organization $organization, Member $member, MemberMergeIntoRequest $request, MemberService $memberService): JsonResponse
    {
        $this->checkPermission($organization, 'members:merge-into', $member);

        $user = $member->user;
        if ($member->role !== Role::Placeholder->value || ! $user->is_placeholder) {
            throw new OnlyPlaceholdersCanBeMergedIntoAnotherMember;
        }
        $memberTo = Member::findOrFail($request->getMemberId());

        DB::transaction(function () use ($organization, $member, $user, $memberTo, $memberService): void {
            $memberService->assignOrganizationEntitiesToDifferentMember($organization, $member, $memberTo);
            $member->delete();
            $user->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * Invite a placeholder member to become a real member of the organization
     *
     * @throws AuthorizationException
     * @throws UserNotPlaceholderApiException
     * @throws UserIsAlreadyMemberOfOrganizationApiException
     * @throws ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException
     * @throws InvitationForTheEmailAlreadyExistsApiException
     *
     * @operationId invitePlaceholder
     */
    public function invitePlaceholder(Organization $organization, Member $member, InvitationService $invitationService): JsonResponse
    {
        $this->checkPermission($organization, 'members:invite-placeholder', $member);
        $user = $member->user;

        if (! $user->is_placeholder) {
            throw new UserNotPlaceholderApiException;
        }

        if (Str::endsWith($user->email, '@solidtime-import.test')) {
            throw new ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException;
        }

        $invitationService->inviteUser($organization, $user->email, Role::Employee);

        return response()->json(null, 204);
    }
}
