<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\Member\PersonalMembershipCollection;
use App\Models\Member;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMembershipController extends Controller
{
    /**
     * Get the memberships of the current user
     *
     * This endpoint is independent of organization.
     *
     * @operationId getMyMemberships
     *
     * @return PersonalMembershipCollection
     *
     * @throws AuthorizationException
     */
    public function myMemberships(): JsonResource
    {
        $user = $this->user();

        $members = Member::query()
            ->whereBelongsTo($user, 'user')
            ->with(['organization'])
            ->get();

        return new PersonalMembershipCollection($members);
    }
}
