<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;

class MemberRemoved
{
    use Dispatchable;

    public Organization $organization;

    public Member $member;

    public function __construct(Member $member, Organization $organization)
    {
        $this->member = $member;
        $this->organization = $organization;
    }
}
