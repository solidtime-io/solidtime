<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers;
use App\Http\Resources\V1\User\UserResource;
use App\Models\User;
use App\Service\DeletionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Get the current user
     *
     * This endpoint is independent of organization.
     *
     * @operationId getMe
     *
     * @throws AuthorizationException
     */
    public function me(): UserResource
    {
        $user = $this->user();

        return new UserResource($user);
    }

    /**
     * Handles the deletion of a user.
     *
     * This endpoint is independent of organization.
     *
     * @operationId deleteUser
     *
     * @param  User  $user  The user instance to be deleted.
     * @param  DeletionService  $deletionService  The service responsible for performing the user deletion.
     * @return JsonResponse A JSON response with a 204 No Content status upon successful deletion.
     *
     * @throws AuthorizationException Thrown when the authenticated user does not match the user to be deleted.
     * @throws CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers Thrown when the user to be deleted is the owner of an organization with multiple members.
     */
    public function destroy(User $user, DeletionService $deletionService): JsonResponse
    {
        if ($user->getKey() !== $this->user()->getKey()) {
            throw new AuthorizationException;
        }

        $deletionService->deleteUser($user);

        return response()->json(null, 204);
    }
}
