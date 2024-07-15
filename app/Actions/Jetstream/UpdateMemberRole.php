<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Enums\Role;
use App\Exceptions\MovedToApiException;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Exception;

class UpdateMemberRole
{
    /**
     * Update the role for the given team member.
     *
     * @throws Exception
     */
    public function update(User $actingUser, Organization $organization, string $userId, string $role): void
    {
        throw new MovedToApiException();
    }
}
