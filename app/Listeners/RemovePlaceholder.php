<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Member;
use App\Models\User;
use App\Service\MemberService;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Jetstream\Events\TeamMemberAdded;

class RemovePlaceholder
{
    /**
     * Handle the event.
     */
    public function handle(TeamMemberAdded $event): void
    {
        $memberService = app(MemberService::class);
        $member = Member::query()
            ->whereBelongsTo($event->team, 'organization')
            ->whereBelongsTo($event->user, 'user')
            ->firstOrFail();
        $placeholders = Member::query()
            ->whereHas('user', function (Builder $query) use ($event): void {
                /** @var Builder<User> $query */
                $query->where('is_placeholder', '=', true)
                    ->where('email', '=', $event->user->email);
            })
            ->whereBelongsTo($event->team, 'organization')
            ->with(['user'])
            ->get();

        foreach ($placeholders as $placeholder) {
            /** @var Member $placeholder */
            $placeholderUser = $placeholder->user;
            $memberService->assignOrganizationEntitiesToDifferentMember($event->team, $placeholder, $member);
            $placeholder->delete();
            $placeholderUser->delete();
        }
    }
}
