<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers;
use App\Exceptions\Api\UserResendEmailVerificationNoPendingEmailApiException;
use App\Http\Requests\V1\User\UserDestroyRequest;
use App\Http\Requests\V1\User\UserUpdateCurrentOrganizationRequest;
use App\Http\Requests\V1\User\UserUpdateRequest;
use App\Http\Resources\V1\User\UserResource;
use App\Mail\VerifyUpdatedEmailMail;
use App\Models\Organization;
use App\Models\User;
use App\Service\DeletionService;
use App\Service\UserService;
use App\Support\Base64File;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Get the current user
     *
     * This endpoint is independent of the organization.
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
     * Update the current organization of the current user
     *
     * Switches the organization that the user is currently working in. The user
     * must be a member of the given organization. This endpoint is independent of
     * the organization.
     *
     * @operationId updateMyCurrentOrganization
     *
     * @throws AuthorizationException
     */
    public function updateMyCurrentOrganization(UserUpdateCurrentOrganizationRequest $request, UserService $userService): UserResource
    {
        $user = $this->user();

        /** @var Organization|null $organization */
        $organization = $user->organizations()
            ->whereKey($request->getOrganizationId())
            ->first();

        if ($organization === null) {
            throw new AuthorizationException;
        }

        $userService->switchCurrentOrganization($user, $organization);

        return new UserResource($user->refresh());
    }

    /**
     * Update the current user
     *
     * This endpoint is independent of the organization.
     *
     * @operationId updateUser
     */
    public function update(User $user, UserUpdateRequest $request): UserResource
    {
        if ($user->getKey() !== $this->user()->getKey()) {
            throw new AuthorizationException;
        }

        if ($request->hasPhotoKey()) {
            $photoDisk = (string) config('filesystems.public');
            $previousPhotoPath = $user->profile_photo_path;
            $newPhoto = $request->getPhoto();

            if ($newPhoto === null) {
                $user->profile_photo_path = null;
            } else {
                $decoded = Base64File::decode($newPhoto);
                assert($decoded !== null);
                $extension = Base64File::extension($decoded['mime_type']);
                assert($extension !== null);

                $photoPath = 'profile-photos/'.Str::uuid().'.'.$extension;
                Storage::disk($photoDisk)->put($photoPath, $decoded['data'], 'public');
                $user->profile_photo_path = $photoPath;
            }

            if ($previousPhotoPath !== null) {
                Storage::disk($photoDisk)->delete($previousPhotoPath);
            }
        }

        $emailToVerify = null;
        $email = $request->getEmail();
        if ($email !== null && $email !== Str::lower($user->email)) {
            $emailToVerify = $email;
            $user->pending_email = $email;
        }

        if ($request->getName() !== null) {
            $user->name = $request->getName();
        }

        if ($request->getTimezone() !== null) {
            $user->timezone = $request->getTimezone();
        }

        if ($request->getWeekStart() !== null) {
            $user->week_start = $request->getWeekStart();
        }

        $user->save();

        if ($emailToVerify !== null) {
            Mail::to($emailToVerify)->send(new VerifyUpdatedEmailMail($user, $emailToVerify));
        }

        return new UserResource($user);
    }

    /**
     * Reset the pending email for a user.
     *
     * This endpoint is independent of the organization.
     *
     * @operationId resetUserPendingEmail
     *
     * @throws AuthorizationException Thrown when the authenticated user does not match the user whose email is pending verification.
     */
    public function resetPendingEmail(User $user): JsonResponse
    {
        if ($user->getKey() !== $this->user()->getKey()) {
            throw new AuthorizationException;
        }

        $user->pending_email = null;
        $user->save();

        return response()->json(null, 204);
    }

    /**
     * Resend the pending email update verification email.
     *
     * This endpoint is independent of the organization.
     *
     * @operationId resendUserEmailVerification
     *
     * @throws AuthorizationException Thrown when the authenticated user does not match the user whose email is pending verification.
     * @throws UserResendEmailVerificationNoPendingEmailApiException Thrown when the user does not have a pending email to verify.
     */
    public function resendEmailVerification(User $user): JsonResponse
    {
        if ($user->getKey() !== $this->user()->getKey()) {
            throw new AuthorizationException;
        }

        if ($user->pending_email === null) {
            throw new UserResendEmailVerificationNoPendingEmailApiException;
        }

        Mail::to($user->pending_email)
            ->queue(new VerifyUpdatedEmailMail($user, $user->pending_email));

        return response()->json(null, 204);
    }

    /**
     * Handles the deletion of a user.
     *
     * This endpoint is independent of the organization.
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
    public function destroy(User $user, UserDestroyRequest $request, DeletionService $deletionService): JsonResponse
    {
        if ($user->getKey() !== $this->user()->getKey()) {
            throw new AuthorizationException;
        }

        $deletionService->deleteUser($user);

        return response()->json(null, 204);
    }
}
