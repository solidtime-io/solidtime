<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Organization;
use App\Models\User;
use App\Service\PermissionStore;
use Illuminate\Auth\Access\AuthorizationException;

class ValidateOrganizationDeletion
{
    /**
     * Validate that the team can be deleted by the given user.
     *
     * @param  User  $user  Authenticated user
     * @param  Organization  $organization  Organization to be deleted
     *
     * @throws AuthorizationException
     */
    public function validate(User $user, Organization $organization): void
    {
        if (! app(PermissionStore::class)->userHas($organization, $user, 'organizations:delete')) {
            throw new AuthorizationException();
        }
    }
}
