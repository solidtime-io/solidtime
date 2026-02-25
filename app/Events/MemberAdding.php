<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class MemberAdding
{
    use Dispatchable;

    public User $user;

    public Organization $organization;

    public Role $role;

    public function __construct(User $user, Organization $organization, Role $role)
    {
        $this->user = $user;
        $this->organization = $organization;
        $this->role = $role;
    }
}
