<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Member;
use App\Service\UserService;
use Illuminate\Database\Eloquent\Builder;
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
        $placeholders = Member::query()
            ->whereHas('user', function (Builder $query) use ($event) {
                $query->where('is_placeholder', '=', true)
                    ->where('email', '=', $event->user->email);
            })
            ->whereBelongsTo($event->team, 'organization')
            ->with(['user'])
            ->get();

        foreach ($placeholders as $placeholder) {
            /** @var Member $placeholder */
            $placeholderUser = $placeholder->user;
            $userService->assignOrganizationEntitiesToDifferentUser($event->team, $placeholderUser, $event->user);
            $placeholder->delete();
            $placeholderUser->delete();
        }
    }
}
