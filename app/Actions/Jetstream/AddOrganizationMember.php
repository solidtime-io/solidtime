<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Exceptions\MovedToApiException;
use App\Models\Organization;
use App\Models\User;
use Laravel\Jetstream\Contracts\AddsTeamMembers;

class AddOrganizationMember implements AddsTeamMembers
{
    /**
     * Add a new team member to the given team.
     */
    public function add(User $owner, Organization $organization, string $email, ?string $role = null): void
    {
        throw new MovedToApiException;
    }
}
