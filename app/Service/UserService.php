<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Role;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;

class UserService
{
    /**
     * Assign all organization entities (time entries, project members) from one user to another.
     * This is useful when a placeholder user is replaced with a real user.
     */
    public function assignOrganizationEntitiesToDifferentUser(Organization $organization, User $fromUser, User $toUser): void
    {
        // Time entries
        TimeEntry::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereBelongsTo($fromUser, 'user')
            ->update([
                'user_id' => $toUser->getKey(),
            ]);

        // Project members
        ProjectMember::query()
            ->whereBelongsToOrganization($organization)
            ->whereBelongsTo($fromUser, 'user')
            ->update([
                'user_id' => $toUser->getKey(),
            ]);
    }

    /**
     * Change the ownership of an organization to a new user.
     * The previous owner will be demoted to an admin.
     */
    public function changeOwnership(Organization $organization, User $newOwner): void
    {
        $organization->update([
            'user_id' => $newOwner->getKey(),
        ]);
        $userMembership = Membership::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereBelongsTo($newOwner, 'user')
            ->first();
        $userMembership->role = Role::Owner->value;
        $userMembership->save();
        $oldOwners = Membership::query()
            ->whereBelongsTo($organization, 'organization')
            ->where('role', '=', Role::Owner->value)
            ->where('user_id', '!=', $newOwner->getKey())
            ->get();
        foreach ($oldOwners as $oldOwner) {
            $oldOwner->role = Role::Admin->value;
            $oldOwner->save();
        }
    }
}
