<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Invitation\InvitationIndexRequest;
use App\Http\Requests\V1\Invitation\InvitationStoreRequest;
use App\Http\Resources\V1\Invitation\InvitationCollection;
use App\Http\Resources\V1\Invitation\InvitationResource;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;

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
            ->paginate();

        return InvitationCollection::make($invitations);
    }

    /**
     * Invite a user to the organization
     *
     * @throws AuthorizationException
     *
     * @operationId invite
     */
    public function store(Organization $organization, InvitationStoreRequest $request): JsonResponse
    {
        $this->checkPermission($organization, 'invitations:create');

        app(InvitesTeamMembers::class)->invite(
            $request->user(),
            $organization,
            $request->input('email'),
            $request->input('role')
        );

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
