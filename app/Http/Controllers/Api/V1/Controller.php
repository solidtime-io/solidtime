<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use App\Service\PermissionStore;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Controller extends \App\Http\Controllers\Controller
{
    public function __construct(
        protected PermissionStore $permissionStore,
    ) {
    }

    /**
     * @throws AuthorizationException
     */
    protected function checkPermission(Organization $organization, string $permission): void
    {
        if (! $this->permissionStore->has($organization, $permission)) {
            throw new AuthorizationException();
        }
    }

    /**
     * @param  array<string>  $permissions
     *
     * @throws AuthorizationException
     */
    protected function checkAnyPermission(Organization $organization, array $permissions): void
    {
        foreach ($permissions as $permission) {
            if ($this->permissionStore->has($organization, $permission)) {
                return;
            }
        }
        throw new AuthorizationException();
    }

    protected function hasPermission(Organization $organization, string $permission): bool
    {
        return $this->permissionStore->has($organization, $permission);
    }

    /**
     * @throws AuthorizationException
     */
    protected function user(): User
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            Log::error('This function should only be called in authenticated context');
            throw new AuthorizationException();
        }

        return $user;
    }

    /**
     * @throws AuthorizationException
     */
    protected function member(Organization $organization): Member
    {
        $user = $this->user();
        $member = Member::query()->whereBelongsTo($organization, 'organization')->whereBelongsTo($user, 'user')->first();
        if ($member === null) {
            Log::error('This function should only be called in authenticated context after checking the user is a member of the organization');
            throw new AuthorizationException();
        }

        return $member;
    }
}
