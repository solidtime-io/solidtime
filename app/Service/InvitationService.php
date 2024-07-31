<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Role;
use App\Exceptions\Api\UserIsAlreadyMemberOfOrganizationApiException;
use App\Mail\OrganizationInvitationMail;
use App\Models\Member;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Events\InvitingTeamMember;

class InvitationService
{
    /**
     * @throws UserIsAlreadyMemberOfOrganizationApiException
     */
    public function inviteUser(Organization $organization, string $email, Role $role): OrganizationInvitation
    {
        if (Member::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereRelation('user', 'email', '=', $email)
            ->where('role', '!=', Role::Placeholder->value)
            ->exists()) {
            throw new UserIsAlreadyMemberOfOrganizationApiException;
        }

        InvitingTeamMember::dispatch($organization, $email, $role->value);

        $invitation = new OrganizationInvitation;
        $invitation->email = $email;
        $invitation->role = $role->value;
        $invitation->organization()->associate($organization);
        $invitation->save();

        Mail::to($email)->queue(new OrganizationInvitationMail($invitation));

        return $invitation;
    }
}
