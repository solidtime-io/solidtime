<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Role;
use App\Events\MemberRemoved;
use App\Exceptions\Api\CanNotRemoveOwnerFromOrganization;
use App\Exceptions\Api\ChangingRoleOfPlaceholderIsNotAllowed;
use App\Exceptions\Api\ChangingRoleToPlaceholderIsNotAllowed;
use App\Exceptions\Api\EntityStillInUseApiException;
use App\Exceptions\Api\OnlyOwnerCanChangeOwnership;
use App\Exceptions\Api\OrganizationNeedsAtLeastOneOwner;
use App\Models\Member;
use App\Models\Organization;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Jetstream\Events\AddingTeamMember;
use Laravel\Jetstream\Events\TeamMemberAdded;

class MemberService
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function addMember(User $user, Organization $organization, Role $role, bool $asSuperAdmin = false): Member
    {
        if (! $asSuperAdmin) {
            AddingTeamMember::dispatch($organization, $user);
        }

        $member = new Member;
        DB::transaction(function () use ($organization, $user, $role, &$member): void {
            $member->user()->associate($user);
            $member->organization()->associate($organization);
            $member->role = $role->value;
            $member->save();
        });

        if (! $asSuperAdmin) {
            TeamMemberAdded::dispatch($organization, $user);
        }

        return $member;
    }

    /**
     * @throws CanNotRemoveOwnerFromOrganization
     * @throws EntityStillInUseApiException
     */
    public function removeMember(Member $member, Organization $organization): void
    {
        if (TimeEntry::query()->where('user_id', $member->user_id)->whereBelongsTo($organization, 'organization')->exists()) {
            throw new EntityStillInUseApiException('member', 'time_entry');
        }
        if (ProjectMember::query()->whereBelongsToOrganization($organization)->where('user_id', $member->user_id)->exists()) {
            throw new EntityStillInUseApiException('member', 'project_member');
        }
        if ($member->role === Role::Owner->value) {
            throw new CanNotRemoveOwnerFromOrganization;
        }

        $member->delete();
        MemberRemoved::dispatch($member, $organization);
    }

    /**
     * @throws ChangingRoleToPlaceholderIsNotAllowed
     * @throws OnlyOwnerCanChangeOwnership
     * @throws OrganizationNeedsAtLeastOneOwner
     * @throws ChangingRoleOfPlaceholderIsNotAllowed
     */
    public function changeRole(Member $member, Organization $organization, Role $newRole, bool $allowOwnerChange): void
    {
        $oldRole = Role::from($member->role);
        if ($oldRole === Role::Owner) {
            throw new OrganizationNeedsAtLeastOneOwner;
        }
        if ($oldRole === Role::Placeholder) {
            throw new ChangingRoleOfPlaceholderIsNotAllowed;
        }
        if ($newRole === Role::Placeholder) {
            throw new ChangingRoleToPlaceholderIsNotAllowed;
        }
        if ($newRole === Role::Owner) {
            if ($allowOwnerChange) {
                $this->changeOwnership($organization, $member);
            } else {
                throw new OnlyOwnerCanChangeOwnership;
            }
        } else {
            $member->role = $newRole->value;
        }
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
