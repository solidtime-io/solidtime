<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\Role;
use App\Events\BeforeOrganizationDeletion;
use App\Exceptions\Api\CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers;
use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeletionService
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function deleteOrganization(Organization $organization, bool $inTransaction = true): void
    {
        if ($inTransaction) {
            DB::transaction(function () use ($organization) {
                $this->deleteOrganization($organization, false);
            });

            return;
        }

        Log::debug('Start deleting organization', [
            'organization_id' => $organization->getKey(),
            'name' => $organization->name,
            'owner_id' => $organization->user_id,
        ]);

        BeforeOrganizationDeletion::dispatch($organization);

        // Delete all organization invitations
        OrganizationInvitation::query()->whereBelongsTo($organization, 'organization')->delete();

        // Delete all time entries
        TimeEntry::query()->whereBelongsTo($organization, 'organization')->delete();

        // Delete all tags
        Tag::query()->whereBelongsTo($organization, 'organization')->delete();

        // Delete all tasks
        Task::query()->whereBelongsTo($organization, 'organization')->delete();

        // Delete all project members
        ProjectMember::query()->whereBelongsToOrganization($organization)->delete();

        // Delete all projects
        Project::query()->whereBelongsTo($organization, 'organization')->delete();

        // Delete all clients
        Client::query()->whereBelongsTo($organization, 'organization')->delete();

        // Reset the current organization
        $organization->owner()
            ->where('current_team_id', $organization->getKey())
            ->update(['current_team_id' => null]);

        $organization->users()
            ->where('current_team_id', $organization->getKey())
            ->update(['current_team_id' => null]);

        // Delete all members
        $users = $organization->users()
            ->with([
                'currentOrganization',
            ])
            ->get();
        $organization->users()->sync([]);

        // Make sure all users have at least one organization
        foreach ($users as $user) {
            if ($user->is_placeholder) {
                $user->delete();
            } else {
                $this->userService->makeSureUserHasAtLeastOneOrganization($user);
                $this->userService->makeSureUserHasCurrentOrganization($user);
            }
        }

        // Delete organization
        $organization->delete();

        Log::debug('Finished deleting organization', [
            'organization_id' => $organization->getKey(),
            'name' => $organization->name,
            'owner_id' => $organization->user_id,
        ]);
    }

    /**
     * @throws CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers
     */
    public function deleteUser(User $user, bool $inTransaction = true): void
    {
        if ($inTransaction) {
            DB::transaction(function () use ($user) {
                $this->deleteUser($user, false);
            });

            return;
        }

        Log::debug('Start deleting user', [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $members = Member::query()->whereBelongsTo($user, 'user')
            ->with([
                'organization',
                'user',
            ])
            ->get();

        foreach ($members as $member) {
            if ($member->role === Role::Owner->value && $member->organization->users()->count() > 1) {
                throw new CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers();
            }
        }

        /** @var Member $member */
        foreach ($members as $member) {
            if ($member->role === Role::Owner->value) {
                // Note: The member needs to be deleted first, otherwise the organization delete function will recreate a new personal organization for the user
                $member->delete();
                $this->deleteOrganization($member->organization, false);
            } else {
                $this->userService->makeMemberToPlaceholder($member);
            }
        }

        // Note: Since the deletion of the profile photo is not reversible via a database rollback this needs to be done last
        $user->deleteProfilePhoto();

        $user->delete();

        Log::debug('Finished deleting user', [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
