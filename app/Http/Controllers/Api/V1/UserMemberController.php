<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\Member\PersonalMemberCollection;
use App\Http\Resources\V1\Member\PersonalMemberResource;
use App\Models\Member;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMemberController extends Controller
{
    /**
     * Get the memberships of the current user
     *
     * This endpoint is independent of organization.
     *
     * @operationId getMyMemberships
     *
     * @return PersonalMemberCollection<PersonalMemberResource>
     *
     * @throws AuthorizationException
     */
    public function myMembers(): JsonResource
    {
        $user = $this->user();

        $members = Member::query()
            ->whereBelongsTo($user, 'user')
            ->with(['organization'])
            ->paginate(config('app.pagination_per_page_default'));

        return new PersonalMemberCollection($members);
    }
}
