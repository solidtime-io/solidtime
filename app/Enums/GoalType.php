<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum GoalType: string
{
    use LaravelEnumHelper;

    /**
     * A goal a member set for themselves. Only that member can see and manage it, no permission reaches it.
     */
    case Personal = 'personal';

    /**
     * A goal the organization set for a member or for the whole organization.
     * Managed by the members that may manage goals, visible to them and to the member the goal is for.
     * Requires the team goals extension.
     */
    case Organization = 'organization';
}
