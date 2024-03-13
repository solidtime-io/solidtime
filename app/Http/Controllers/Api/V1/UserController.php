<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\UserNotPlaceholderApiException;
use App\Http\Requests\V1\User\UserIndexRequest;
use App\Http\Resources\V1\User\UserCollection;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;

class UserController extends Controller
{
    /**
     * List all users in an organization
     *
     * @throws AuthorizationException
     */
    public function index(Organization $organization, UserIndexRequest $request): UserCollection
    {
        $this->checkPermission($organization, 'users:view');

        $users = $organization->users()
            ->paginate();

        return UserCollection::make($users);
    }

    /**
     * Invite a placeholder user to become a real user in the organization
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

        return response()->json($user);
    }
}
