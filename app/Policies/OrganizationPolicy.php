<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Service\PermissionStore;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if (Filament::isServing()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organization $organization): bool
    {
        if (Filament::isServing()) {
            return true;
        }

        return $user->belongsToTeam($organization);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (Filament::isServing()) {
            return true;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): bool
    {
        if (Filament::isServing()) {
            return true;
        }

        return app(PermissionStore::class)->userHas($organization, $user, 'organizations:update');
    }

    /**
     * Determine whether the user can update team member permissions.
     */
    public function updateTeamMember(User $user, Organization $organization): bool
    {
        if (Filament::isServing()) {
            return true;
        }

        // Note: since this policy is only used for jetstream endpoints, we can return false here
        return false;
    }

    /**
     * Determine whether the user can remove team members.
     */
    public function removeTeamMember(User $user, Organization $organization): bool
    {
        if (Filament::isServing()) {
            return true;
        }

        // Note: since this policy is only used for jetstream endpoints that are no longer in use, we can return false here
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): bool
    {
        if (Filament::isServing()) {
            return true;
        }

        return $user->ownsTeam($organization);
    }
}
