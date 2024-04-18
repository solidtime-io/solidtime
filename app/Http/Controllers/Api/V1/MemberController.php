<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\EntityStillInUseApiException;
use App\Exceptions\Api\UserNotPlaceholderApiException;
use App\Http\Requests\V1\Member\MemberIndexRequest;
use App\Http\Requests\V1\Member\MemberUpdateRequest;
use App\Http\Resources\V1\Member\MemberCollection;
use App\Http\Resources\V1\Member\MemberPivotResource;
use App\Http\Resources\V1\Member\MemberResource;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;

class MemberController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?Membership $membership = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($membership !== null && $membership->organization_id !== $organization->id) {
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
            ->paginate();

        return MemberCollection::make($members);
    }

    /**
     * Update a member of the organization
     *
     * @throws AuthorizationException
     *
     * @operationId updateMember
     */
    public function update(Organization $organization, Membership $membership, MemberUpdateRequest $request): JsonResource
    {
        $this->checkPermission($organization, 'members:update', $membership);

        $membership->billable_rate = $request->input('billable_rate');
        $membership->role = $request->input('role');
        $membership->save();

        return new MemberResource($membership);
    }

    /**
     * Remove a member of the organization.
     *
     * @throws AuthorizationException|EntityStillInUseApiException
     *
     * @operationId removeMember
     */
    public function destroy(Organization $organization, Membership $membership): JsonResponse
    {
        $this->checkPermission($organization, 'members:delete', $membership);

        if (TimeEntry::query()->where('user_id', $membership->user_id)->whereBelongsTo($organization, 'organization')->exists()) {
            throw new EntityStillInUseApiException('member', 'time_entry');
        }
        if (ProjectMember::query()->whereBelongsToOrganization($organization)->where('user_id', $membership->user_id)->exists()) {
            throw new EntityStillInUseApiException('member', 'project_member');
        }

        $membership->delete();

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
    public function invitePlaceholder(Organization $organization, Membership $membership, Request $request): JsonResponse
    {
        $this->checkPermission($organization, 'members:invite-placeholder', $membership);
        $user = $membership->user;

        if (! $user->is_placeholder) {
            throw new UserNotPlaceholderApiException();
        }

        app(InvitesTeamMembers::class)->invite(
            $request->user(),
            $organization,
            $user->email,
            'employee'
        );

        return response()->json(null, 204);
    }
}
