<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Exceptions\MovedToApiException;
use App\Models\Organization;
use App\Models\User;
use Exception;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;

class InviteOrganizationMember implements InvitesTeamMembers
{
    /**
     * Invite a new team member to the given team.
     *
     * @throws Exception
     */
    public function invite(User $user, Organization $organization, string $email, ?string $role = null): void
    {
        throw new MovedToApiException();
    }
}
