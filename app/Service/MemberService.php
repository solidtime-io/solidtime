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
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

            $user->currentOrganization()->associate($organization);
            $user->save();
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
    public function removeMember(Member $member, Organization $organization, bool $withRelations = false): void
    {
        if ($member->role === Role::Owner->value) {
            throw new CanNotRemoveOwnerFromOrganization;
        }

        $user = $member->user;
        $isPlaceholder = $user->is_placeholder;

        if (! $isPlaceholder && $user->current_team_id === $member->organization_id) {
            $user->currentTeam()->disassociate();
            $user->save();
        }

        if ($withRelations) {
            TimeEntry::query()->where('user_id', $member->user_id)->whereBelongsTo($organization, 'organization')->delete();
            ProjectMember::query()->whereBelongsToOrganization($organization)->where('user_id', $member->user_id)->delete();
        } else {
            if (TimeEntry::query()->where('user_id', $member->user_id)->whereBelongsTo($organization, 'organization')->exists()) {
                throw new EntityStillInUseApiException('member', 'time_entry');
            }
            if (ProjectMember::query()->whereBelongsToOrganization($organization)->where('user_id', $member->user_id)->exists()) {
                throw new EntityStillInUseApiException('member', 'project_member');
            }
        }

        $member->delete();

        if ($isPlaceholder) {
            $user->delete();
        } else {
            $this->userService->makeSureUserHasAtLeastOneOrganization($user);
            $this->userService->makeSureUserHasCurrentOrganization($user);
        }

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

    public function assignOrganizationEntitiesToDifferentMember(Organization $organization, Member $fromMember, Member $toMember): void
    {
        // Time entries
        TimeEntry::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereBelongsTo($fromMember, 'member')
            ->update([
                'user_id' => $toMember->user_id,
                'member_id' => $toMember->getKey(),
            ]);

        // Project members
        ProjectMember::query()
            ->whereBelongsToOrganization($organization)
            ->whereBelongsTo($fromMember, 'member')
            ->whereDoesntHave('project', function (Builder $builder) use ($toMember): void {
                /** @var Builder<Project> $builder */
                $builder->whereHas('members', function (Builder $builder) use ($toMember): void {
                    /** @var Builder<ProjectMember> $builder */
                    $builder->where('member_id', $toMember->getKey());
                });
            })
            ->update([
                'user_id' => $toMember->user_id,
                'member_id' => $toMember->getKey(),
            ]);

        ProjectMember::query()
            ->whereBelongsToOrganization($organization)
            ->whereBelongsTo($fromMember, 'member')
            ->delete();
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
        if ($user->current_team_id === $member->organization_id) {
            $user->currentTeam()->disassociate();
            $user->save();
        }

        $placeholderUser = $user->replicate();
        $placeholderUser->is_placeholder = true;
        $placeholderUser->current_team_id = $member->organization_id;
        $placeholderUser->save();

        $member->user()->associate($placeholderUser);
        $member->role = Role::Placeholder->value;
        $member->save();

        $this->userService->assignOrganizationEntitiesToDifferentUser($member->organization, $user, $placeholderUser);
        if ($makeSureUserHasAtLeastOneOrganization) {
            $this->userService->makeSureUserHasAtLeastOneOrganization($user);
            $this->userService->makeSureUserHasCurrentOrganization($user);
        }
    }
}
