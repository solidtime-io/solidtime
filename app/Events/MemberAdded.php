<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class MemberAdded
{
    use Dispatchable;

    public Member $member;

    public Organization $organization;

    public User $user;

    public function __construct(Member $member, Organization $organization, User $user)
    {
        $this->member = $member;
        $this->organization = $organization;
        $this->user = $user;
    }
}
