<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PermissionStore
{
    /**
     * @var array<string, array<string>>
     */
    private array $permissionCache = [];

    public function has(Organization $organization, string $permission): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        if (! isset($this->permissionCache[$user->getKey().'|'.$organization->getKey()])) {
            if ($user->ownsTeam($organization)) {
                return true;
            }

            if (! $user->belongsToTeam($organization)) {
                return false;
            }

            $permissions = $user->teamPermissions($organization);
            $this->permissionCache[$user->getKey().'|'.$organization->getKey()] = $permissions;
        } else {
            $permissions = $this->permissionCache[$user->getKey().'|'.$organization->getKey()];
        }

        return in_array($permission, $permissions, true);
    }
}
