<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\InvitationForTheEmailAlreadyExistsApiException;
use App\Exceptions\Api\UserIsAlreadyMemberOfOrganizationApiException;
use App\Http\Requests\V1\Invitation\InvitationIndexRequest;
use App\Http\Requests\V1\Invitation\InvitationStoreRequest;
use App\Http\Resources\V1\Invitation\InvitationCollection;
use App\Http\Resources\V1\Invitation\InvitationResource;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Service\InvitationService;
use App\Service\OrganizationInvitationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class InvitationController extends Controller
{
    protected function checkPermission(Organization $organization, string $permission, ?OrganizationInvitation $organizationInvitation = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($organizationInvitation !== null && $organizationInvitation->organization_id !== $organization->id) {
            throw new AuthorizationException('Invitation does not belong to organization');
        }
    }

    /**
     * List all invitations of an organization
     *
     * @return InvitationCollection<InvitationResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getInvitations
     */
    public function index(Organization $organization, InvitationIndexRequest $request): InvitationCollection
    {
        $this->checkPermission($organization, 'invitations:view');

        $invitations = $organization->teamInvitations()
            ->orderBy('created_at', 'desc')
            ->paginate(config('app.pagination_per_page_default'));

        return InvitationCollection::make($invitations);
    }

    /**
     * Invite a user to the organization
     *
     * @throws AuthorizationException
     * @throws UserIsAlreadyMemberOfOrganizationApiException
     * @throws InvitationForTheEmailAlreadyExistsApiException
     *
     * @operationId invite
     */
    public function store(Organization $organization, InvitationStoreRequest $request, InvitationService $invitationService): JsonResponse
    {
        $this->checkPermission($organization, 'invitations:create');

        $email = $request->getEmail();
        $role = $request->getRole();

        $invitationService->inviteUser($organization, $email, $role);

        return response()->json(null, 204);
    }

    /**
     * Resend email for a pending invitation
     *
     * @throws AuthorizationException
     *
     * @operationId resendInvitationEmail
     */
    public function resend(Organization $organization, OrganizationInvitation $invitation, OrganizationInvitationService $organizationInvitationService): JsonResponse
    {
        $this->checkPermission($organization, 'invitations:resend', $invitation);

        $organizationInvitationService->resend($invitation);

        return response()->json(null, 204);
    }

    /**
     * Remove a pending invitation
     *
     * @throws AuthorizationException
     *
     * @operationId removeInvitation
     */
    public function destroy(Organization $organization, OrganizationInvitation $invitation): JsonResponse
    {
        $this->checkPermission($organization, 'invitations:remove', $invitation);

        $invitation->delete();

        return response()->json(null, 204);
    }
}
