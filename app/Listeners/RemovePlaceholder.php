<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Service\UserService;
use Laravel\Jetstream\Events\TeamMemberAdded;

class RemovePlaceholder
{
    /**
     * Handle the event.
     */
    public function handle(TeamMemberAdded $event): void
    {
        /** @var UserService $userService */
        $userService = app(UserService::class);
        $placeholders = User::query()
            ->where('is_placeholder', '=', true)
            ->where('email', '=', $event->user->email)
            ->belongsToOrganization($event->team)
            ->get();

        foreach ($placeholders as $placeholder) {
            $userService->assignOrganizationEntitiesToDifferentUser($event->team, $placeholder, $event->user);
        }
    }
}
