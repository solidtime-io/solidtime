<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\Organization;
use App\Service\PermissionStore;
use Illuminate\Auth\Access\AuthorizationException;

abstract class Controller extends \App\Http\Controllers\Controller
{
    public function __construct(
        protected PermissionStore $permissionStore,
    ) {}

    /**
     * @throws AuthorizationException
     */
    protected function hasPermission(Organization $organization, string $permission): bool
    {
        return $this->permissionStore->has($organization, $permission);
    }
}
