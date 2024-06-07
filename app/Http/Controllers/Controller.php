<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;

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
        /** @var Member|null $member */
        $member = Member::query()->whereBelongsTo($organization, 'organization')->whereBelongsTo($user, 'user')->first();
        if ($member === null) {
            Log::error('This function should only be called in authenticated context after checking the user is a member of the organization');
            throw new AuthorizationException();
        }

        return $member;
    }

    /**
     * @throws AuthorizationException
     */
    protected function currentOrganization(): Organization
    {
        $user = $this->user();
        $organization = $user->currentTeam;
        if ($organization === null) {
            $organization = $user->organizations()->first();
        }

        return $organization;
    }
}
