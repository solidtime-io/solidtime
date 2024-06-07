<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Role;

class PermissionStore
{
    /**
     * @var array<string, array<string>>
     */
    private array $permissionCache = [];

    public function clear(): void
    {
        $this->permissionCache = [];
    }

    public function has(Organization $organization, string $permission): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        return $this->userHas($organization, $user, $permission);
    }

    public function userHas(Organization $organization, User $user, string $permission): bool
    {
        if (! isset($this->permissionCache[$user->getKey().'|'.$organization->getKey()])) {
            if (! $user->belongsToTeam($organization)) {
                return false;
            }

            $permissions = $this->getPermissionsByUser($organization, $user);
            $this->permissionCache[$user->getKey().'|'.$organization->getKey()] = $permissions;
        } else {
            $permissions = $this->permissionCache[$user->getKey().'|'.$organization->getKey()];
        }

        return in_array($permission, $permissions, true);
    }

    /**
     * @return array<string>
     */
    private function getPermissionsByUser(Organization $organization, User $user): array
    {
        if (! $user->belongsToTeam($organization)) {
            return [];
        }

        $role = $organization->users
            ->where('id', $user->getKey())
            ->first()
            ?->membership
            ?->role;

        if ($role === null) {
            return [];
        }

        /** @var Role|null $roleObj */
        $roleObj = Jetstream::findRole($role);

        return $roleObj?->permissions ?? [];
    }

    /**
     * @return array<string>
     */
    public function getPermissions(Organization $organization): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return [];
        }

        return $this->getPermissionsByUser($organization, $user);
    }
}
