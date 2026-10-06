<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\GoalComparison;
use App\Enums\GoalPeriod;
use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\TimeEntry;
use App\Service\Dto\GoalProgressDto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GoalProgressService
{
    /**
     * Progress of the goal in the period that contains $now.
     */
    public function getCurrentProgress(Goal $goal, Carbon $now): GoalProgressDto
    {
        [$periodStart, $periodEnd] = $this->getPeriodBounds($goal, $now);
        $trackedSeconds = $this->getProgress($goal, $periodStart, $periodEnd);

        return new GoalProgressDto($periodStart, $periodEnd, $trackedSeconds, $this->getStatus($goal, $trackedSeconds));
    }

    /**
     * Progress of the goals in the period that contains $now, keyed by goal ID.
     * Runs one query per goal, since every goal has its own filters, timezone and period.
     *
     * @param  Collection<int, Goal>  $goals
     * @return array<string, GoalProgressDto>
     */
    public function getCurrentProgressForGoals(Collection $goals, Carbon $now): array
    {
        $progress = [];
        foreach ($goals as $goal) {
            $progress[$goal->getKey()] = $this->getCurrentProgress($goal, $now);
        }

        return $progress;
    }

    /**
     * Bounds of the period that contains $date, in UTC. The period is calculated in the timezone of the goal
     * and respects the week start of the goal. The start is inclusive, the end exclusive.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function getPeriodBounds(Goal $goal, Carbon $date): array
    {
        $dateInTimezone = $date->copy()->setTimezone($goal->timezone);

        $start = match ($goal->period) {
            GoalPeriod::Day => $dateInTimezone->copy()->startOfDay(),
            GoalPeriod::Week => $dateInTimezone->copy()->startOfWeek($goal->week_start->carbonWeekDay()),
            GoalPeriod::Month => $dateInTimezone->copy()->startOfMonth(),
        };
        $end = match ($goal->period) {
            GoalPeriod::Day => $start->copy()->addDay(),
            GoalPeriod::Week => $start->copy()->addWeek(),
            GoalPeriod::Month => $start->copy()->addMonth(),
        };

        return [$start->utc(), $end->utc()];
    }

    /**
     * Seconds tracked in the given period that count towards the goal.
     * Counts the time entries of the member of the goal, or of every member for goals without a member.
     * Time entries are assigned to the period their start is in, like in the reporting.
     * A running time entry counts with its elapsed time up to now.
     *
     * @param  Carbon  $periodStart  Start of the period (inclusive)
     * @param  Carbon  $periodEnd  End of the period (exclusive)
     */
    private function getProgress(Goal $goal, Carbon $periodStart, Carbon $periodEnd): int
    {
        $filters = $goal->filters;

        $query = TimeEntry::query()
            ->where('organization_id', '=', $goal->organization_id);
        if ($goal->member_id !== null) {
            $query->where('member_id', '=', $goal->member_id);
        }
        $timeEntryFilter = new TimeEntryFilter($query);
        $timeEntryFilter
            ->addStart($periodStart)
            ->addEnd($periodEnd)
            ->addProjectIdsFilter($filters->projectIds?->toArray())
            ->addTaskIdsFilter($filters->taskIds?->toArray())
            ->addTagIdsFilter($filters->tagIds?->toArray(), $filters->tagMatchType)
            ->addClientIdsFilter($filters->clientIds?->toArray())
            ->addBillable($filters->billable)
            ->addType($filters->timeEntryType);
        if ($goal->member_id === null) {
            $timeEntryFilter->addMemberIdsFilter($filters->memberIds?->toArray());
        }

        /** @var object{tracked_seconds: int|float|string|null} $row */
        $row = $timeEntryFilter->get()
            ->selectRaw('coalesce(round(sum(extract(epoch from (coalesce("end", ?::timestamp) - "start")))), 0) as tracked_seconds', [Carbon::now()])
            ->toBase()
            ->first();

        return max(0, (int) $row->tracked_seconds);
    }

    public function getStatus(Goal $goal, int $trackedSeconds): GoalStatus
    {
        return match ($goal->comparison) {
            GoalComparison::AtLeast => $trackedSeconds >= $goal->target_seconds
                ? GoalStatus::Achieved
                : GoalStatus::InProgress,
            GoalComparison::LessThan => $trackedSeconds < $goal->target_seconds
                ? GoalStatus::OnTrack
                : GoalStatus::Exceeded,
        };
    }
}
