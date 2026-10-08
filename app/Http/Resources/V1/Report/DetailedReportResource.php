<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Report;

use App\Http\Resources\V1\BaseResource;
use App\Models\Report;
use Illuminate\Http\Request;

/**
 * @property Report $resource
 */
class DetailedReportResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|array<string, string|bool|int|null|array<int, string>>>
     */
    public function toArray(Request $request): array
    {
        return [
            /** ID of the report */
            'id' => $this->resource->id,
            /** Name */
            'name' => $this->resource->name,
            /** Description */
            'description' => $this->resource->description,
            /** Whether the report can be accessed via an external link */
            'is_public' => $this->resource->is_public,
            /** @var string|null $public_until Date until the report is public */
            'public_until' => $this->formatDateTime($this->resource->public_until),
            /** @var string|null $shareable_link Get link to access the report externally, not set if the report is private */
            'shareable_link' => $this->resource->getShareableLink(),
            'properties' => [
                /** Type of first grouping */
                'group' => $this->resource->properties->group->value,
                /** Type of second grouping */
                'sub_group' => $this->resource->properties->subGroup->value,
                /** Type of grouping of the historic aggregation (time chart) */
                'history_group' => $this->resource->properties->historyGroup->value,
                /** Start date of the report */
                'start' => $this->formatDateTime($this->resource->properties->start),
                /** End date of the report */
                'end' => $this->formatDateTime($this->resource->properties->end),
                /** Whether the report is active */
                'active' => $this->resource->properties->active,
                /** @var array<string>|null $member_ids Filter by multiple member IDs, member IDs are OR combined */
                'member_ids' => $this->resource->properties->memberIds?->toArray(),
                /** Filter by billable status */
                'billable' => $this->resource->properties->billable,
                /** Filter by time entry type */
                'time_entry_type' => $this->resource->properties->timeEntryType?->value,
                /** @var array<string>|null $client_ids Filter by client IDs, client IDs are OR combined */
                'client_ids' => $this->resource->properties->clientIds?->toArray(),
                /** @var array<string>|null $project_ids Filter by project IDs, project IDs are OR combined */
                'project_ids' => $this->resource->properties->projectIds?->toArray(),
                /** @var array<string>|null $tags_ids Filter by tag IDs, tag IDs are OR combined */
                'tag_ids' => $this->resource->properties->tagIds?->toArray(),
                /** Tag match type */
                'tag_match_type' => $this->resource->properties->tagMatchType?->value,
                /** @var array<string>|null $task_ids Filter by task IDs, task IDs are OR combined */
                'task_ids' => $this->resource->properties->taskIds?->toArray(),
                /** Rounding type for time entries */
                'rounding_type' => $this->resource->properties->roundingType?->value,
                /** Rounding minutes for time entries */
                'rounding_minutes' => $this->resource->properties->roundingMinutes,
            ],
            /** Date when the report was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** Date when the report was last updated */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
