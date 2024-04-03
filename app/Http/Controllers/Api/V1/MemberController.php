<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\UserNotPlaceholderApiException;
use App\Http\Requests\V1\Member\MemberIndexRequest;
use App\Http\Resources\V1\User\MemberCollection;
use App\Http\Resources\V1\User\MemberResource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;

class MemberController extends Controller
{
    /**
     * List all members of an organization
     *
     * @return MemberCollection<MemberResource>>
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
     * Invite a placeholder user to become a member of the organization
     *
     * @throws AuthorizationException|UserNotPlaceholderApiException
     *
     * @operationId invitePlaceholder
     */
    public function invitePlaceholder(Organization $organization, User $user, Request $request): JsonResponse
    {
        $this->checkPermission($organization, 'members:invite-placeholder');

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
