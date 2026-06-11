<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Replaces legacy InvitingTeamMember event.
 */
class OrganizationInvitationAdding
{
    use Dispatchable;

    public Organization $organization;

    public string $email;

    public Role $role;

    public User $inviter;

    public function __construct(
        Organization $organization,
        string $email,
        Role $role,
        User $inviter
    ) {
        $this->role = $role;
        $this->email = $email;
        $this->organization = $organization;
        $this->inviter = $inviter;
    }
}
