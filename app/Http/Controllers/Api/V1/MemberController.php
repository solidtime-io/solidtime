<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\UserNotPlaceholderApiException;
use App\Http\Requests\V1\User\UserIndexRequest;
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
     */
    public function index(Organization $organization, UserIndexRequest $request): MemberCollection
    {
        $this->checkPermission($organization, 'users:view');

        $users = $organization->users()
            ->paginate();

        return MemberCollection::make($users);
    }

    /**
     * Invite a placeholder user to become a member of the organization
     *
     * @throws AuthorizationException|UserNotPlaceholderApiException
     */
    public function invitePlaceholder(Organization $organization, User $user, Request $request): JsonResponse
    {
        $this->checkPermission($organization, 'users:invite-placeholder');

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
