<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Role;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use InvalidArgumentException;

class MemberService
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Change the ownership of an organization to a new user.
     * The previous owner will be demoted to an admin.
     */
    public function changeOwnership(Organization $organization, Member $newOwner): void
    {
        $organization->update([
            'user_id' => $newOwner->user_id,
        ]);
        if ($newOwner->organization_id !== $organization->getKey()) {
            throw new InvalidArgumentException('Member is not part of the organization');
        }
        $newOwner->role = Role::Owner->value;
        $newOwner->save();
        $oldOwners = Member::query()
            ->whereBelongsTo($organization, 'organization')
            ->where('role', '=', Role::Owner->value)
            ->where('id', '!=', $newOwner->getKey())
            ->get();
        foreach ($oldOwners as $oldOwner) {
            $oldOwner->role = Role::Admin->value;
            $oldOwner->save();
        }
    }

    public function makeMemberToPlaceholder(Member $member, bool $makeSureUserHasAtLeastOneOrganization = true): void
    {
        $user = $member->user;
        $placeholderUser = $user->replicate();
        $placeholderUser->is_placeholder = true;
        $placeholderUser->save();

        $member->user()->associate($placeholderUser);
        $member->role = Role::Placeholder->value;
        $member->save();

        $this->userService->assignOrganizationEntitiesToDifferentMember($member->organization, $user, $placeholderUser, $member);
        if ($makeSureUserHasAtLeastOneOrganization) {
            $this->userService->makeSureUserHasAtLeastOneOrganization($user);
        }
    }
}
