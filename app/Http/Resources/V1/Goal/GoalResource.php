<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Goal;

use App\Http\Resources\V1\BaseResource;
use App\Models\Goal;
use App\Service\Dto\GoalProgressDto;
use Illuminate\Http\Request;

/**
 * @property Goal $resource
 */
class GoalResource extends BaseResource
{
    private GoalProgressDto $progress;

    public function __construct(Goal $resource, GoalProgressDto $progress)
    {
        parent::__construct($resource);

        $this->progress = $progress;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|array<string, string|bool|int|null|array<int, string>>>
     */
    public function toArray(Request $request): array
    {
        return [
            /** ID of the goal */
            'id' => $this->resource->id,
            /** Name */
            'name' => $this->resource->name,
            /** personal: a goal a member set for themselves, organization: a goal of the organization (team goals extension) */
            'type' => $this->resource->type->value,
            /** Whether the goal is reached with "at least" or "less than" the target time (at_least, less_than) */
            'comparison' => $this->resource->comparison->value,
            /** Target time in seconds */
            'target_seconds' => $this->resource->target_seconds,
            /** Recurring time frame (day, week, month) */
            'period' => $this->resource->period->value,
            /** ID of the member whose time entries count towards the goal, null if every member counts */
            'member_id' => $this->resource->member_id,
            /** Name of the member whose time entries count towards the goal, null if every member counts */
            'member_name' => $this->resource->member?->user->name,
            /** Timezone that defines the periods of the goal */
            'timezone' => $this->resource->timezone,
            /** Week start that defines weekly periods */
            'week_start' => $this->resource->week_start->value,
            /** Whether the goal is archived */
            'is_archived' => $this->resource->is_archived,
            'filters' => [
                /** @var array<string>|null $member_ids Filter by member IDs, member IDs are OR combined, only for goals that count every member */
                'member_ids' => $this->resource->filters->memberIds?->toArray(),
                /** @var array<string>|null $project_ids Filter by project IDs, project IDs are OR combined */
                'project_ids' => $this->resource->filters->projectIds?->toArray(),
                /** @var array<string>|null $task_ids Filter by task IDs, task IDs are OR combined */
                'task_ids' => $this->resource->filters->taskIds?->toArray(),
                /** @var array<string>|null $tag_ids Filter by tag IDs, tag IDs are OR combined */
                'tag_ids' => $this->resource->filters->tagIds?->toArray(),
                /** Tag match type (contains, not_contains) */
                'tag_match_type' => $this->resource->filters->tagMatchType?->value,
                /** @var array<string>|null $client_ids Filter by client IDs, client IDs are OR combined */
                'client_ids' => $this->resource->filters->clientIds?->toArray(),
                /** Filter by billable status */
                'billable' => $this->resource->filters->billable,
                /** Filter by time entry type (work, break) */
                'time_entry_type' => $this->resource->filters->timeEntryType?->value,
            ],
            'progress' => [
                /** Start of the current period (inclusive) */
                'period_start' => $this->formatDateTime($this->progress->periodStart),
                /** End of the current period (exclusive) */
                'period_end' => $this->formatDateTime($this->progress->periodEnd),
                /** Seconds tracked in the current period that match the filters, incl. the running time entry */
                'tracked_seconds' => $this->progress->trackedSeconds,
                /** Status in the current period (in_progress, achieved, on_track, exceeded) */
                'status' => $this->progress->status->value,
            ],
            /** Date when the goal was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** Date when the goal was last updated */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
