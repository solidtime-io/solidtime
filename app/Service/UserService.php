<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Role;
use App\Events\AfterCreateOrganization;
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

        $this->assignOrganizationEntitiesToDifferentMember($organization, $fromUser, $toUser, $toMember);
    }

    public function assignOrganizationEntitiesToDifferentMember(Organization $organization, User $fromUser, User $toUser, Member $toMember): void
    {
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

    public function makeSureUserHasAtLeastOneOrganization(User $user): void
    {
        if ($user->organizations()->count() > 0) {
            return;
        }

        // Create a new organization
        $organization = new Organization();
        $organization->name = $user->name."'s Organization";
        $organization->personal_team = true;
        $organization->user_id = $user->id;
        $organization->save();

        // Attach the user to the organization
        $organization->users()->attach($user, ['role' => Role::Owner->value]);

        // Set the organization as the user's current organization
        $user->currentOrganization()->associate($organization);
        $user->save();

        AfterCreateOrganization::dispatch($organization);
    }

    public function makeSureUserHasCurrentOrganization(User $user): void
    {
        if ($user->currentOrganization !== null) {
            return;
        }

        $organization = $user->organizations()->first();
        $user->currentOrganization()->associate($organization);
        $user->save();
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
