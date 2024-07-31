<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Exceptions\MovedToApiException;
use App\Models\Organization;
use App\Models\User;
use Exception;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;

class RemoveOrganizationMember implements RemovesTeamMembers
{
    /**
     * Remove the team member from the given team.
     *
     * @throws Exception
     */
    public function remove(User $user, Organization $organization, User $teamMember): void
    {
        throw new MovedToApiException;
    }
}
