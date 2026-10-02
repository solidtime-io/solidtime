<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\GoalType;
use App\Models\Goal;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;

/**
 * Decides which goals a member can reach. The `goals:*:own` permissions gate the feature; this decides the rows.
 * Core only has personal goals, reachable by their member alone (not even admins or owners).
 * The team goals extension rebinds this to add organization goals.
 */
class GoalsContract
{
    /**
     * Whether $actor is allowed to create a goal of the given type for the given member.
     *
     * @param  string|null  $targetMemberId  Member whose time entries count, null for every member (organization goals only)
     */
    public function canCreateGoal(Member $actor, GoalType $type, ?string $targetMemberId): bool
    {
        return $type === GoalType::Personal && $targetMemberId === $actor->getKey();
    }

    /**
     * Whether $actor is allowed to see the goal (including its progress).
     */
    public function canViewGoal(Member $actor, Goal $goal): bool
    {
        return $goal->isPersonalGoalOf($actor);
    }

    /**
     * Whether $actor is allowed to update the goal (including its filters and archiving it).
     */
    public function canUpdateGoal(Member $actor, Goal $goal): bool
    {
        return $goal->isPersonalGoalOf($actor);
    }

    /**
     * Whether $actor is allowed to delete the goal.
     */
    public function canDeleteGoal(Member $actor, Goal $goal): bool
    {
        return $goal->isPersonalGoalOf($actor);
    }

    /**
     * Restrict the query to the goals that $actor is allowed to see.
     *
     * @param  Builder<Goal>  $query
     * @return Builder<Goal>
     */
    public function scopeVisibleGoals(Builder $query, Member $actor): Builder
    {
        return $query->where('type', '=', GoalType::Personal->value)
            ->where('member_id', '=', $actor->getKey());
    }
}
