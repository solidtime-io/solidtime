<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Organization;
use App\Models\TimeEntry;
use App\Models\User;

class UserService
{
    public function assignOrganizationEntitiesToDifferentUser(Organization $organization, User $fromUser, User $toUser): void
    {
        // Time entries
        dump(TimeEntry::query()
            ->whereBelongsTo($organization, 'organization')
            ->whereBelongsTo($fromUser, 'user')
            ->update([
                'user_id' => $toUser->getKey(),
            ]));
    }
}
