<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Role;
use App\Exceptions\Api\InvitationForTheEmailAlreadyExistsApiException;
use App\Exceptions\Api\UserIsAlreadyMemberOfOrganizationApiException;
use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Events\InvitingTeamMember;

class InvitationService
{
    /**
     * @throws UserIsAlreadyMemberOfOrganizationApiException|InvitationForTheEmailAlreadyExistsApiException
     */
    public function inviteUser(Organization $organization, string $email, Role $role): OrganizationInvitation
    {
        if (app(MemberService::class)->isEmailAlreadyMember($organization, $email)) {
            throw new UserIsAlreadyMemberOfOrganizationApiException;
        }

        if (OrganizationInvitation::query()
            ->where('email', $email)
            ->whereBelongsTo($organization, 'organization')
            ->exists()) {
            throw new InvitationForTheEmailAlreadyExistsApiException;
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

    /**
     * @return Collection<int, Organization>
     */
    public function processAcceptedInvitations(User $user): Collection
    {
        $organizations = new Collection;

        $invitations = OrganizationInvitation::query()
            ->where('email', $user->email)
            ->whereNotNull('accepted_at')
            ->get();

        foreach ($invitations as $invitation) {
            $organization = $invitation->organization;
            $role = Role::tryFrom($invitation->role);
            if ($role === null) {
                Log::error('Invalid role in invitation', [
                    'invitation' => $invitation->getKey(),
                    'role' => $invitation->role,
                ]);

                continue;
            }
            app(MemberService::class)->addMember($user, $organization, $role);

            $invitation->delete();

            $organizations->push($organization);
        }

        return $organizations;
    }
}
