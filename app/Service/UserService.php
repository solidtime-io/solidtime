<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Role;
use App\Models\Member;
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
        /** @var Member|null $toMember */
        $toMember = Member::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereBelongsTo($toUser, 'user')
            ->first();
        if ($toMember === null) {
            throw new \InvalidArgumentException('User is not a member of the organization');
        }

        // Time entries
        TimeEntry::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereBelongsTo($fromUser, 'user')
            ->update([
                'user_id' => $toUser->getKey(),
                'member_id' => $toMember->getKey(),
            ]);

        // Project members
        ProjectMember::query()
            ->whereBelongsToOrganization($organization)
            ->whereBelongsTo($fromUser, 'user')
            ->update([
                'user_id' => $toUser->getKey(),
                'member_id' => $toMember->getKey(),
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
        /** @var Member|null $userMembership */
        $userMembership = Member::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereBelongsTo($newOwner, 'user')
            ->first();
        if ($userMembership === null) {
            throw new \InvalidArgumentException('User is not a member of the organization');
        }
        $userMembership->role = Role::Owner->value;
        $userMembership->save();
        $oldOwners = Member::query()
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
