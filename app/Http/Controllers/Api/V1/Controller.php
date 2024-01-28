<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class Controller extends \App\Http\Controllers\Controller
{
    /**
     * @throws AuthorizationException
     */
    protected function checkPermission(Organization $organization, string $permission): void
    {
        if (! Auth::user()->hasTeamPermission($organization, $permission)) {
            throw new AuthorizationException();
        }
    }
}
