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
            /** @var string $id ID of the report */
            'id' => $this->resource->id,
            /** @var string $name Name */
            'name' => $this->resource->name,
            /** @var string|null $email Description */
            'description' => $this->resource->description,
            /** @var bool $is_public Whether the report can be accessed via an external link */
            'is_public' => $this->resource->is_public,
            /** @var string|null $public_until Date until the report is public */
            'public_until' => $this->formatDateTime($this->resource->public_until),
            /** @var string|null $shareable_link Get link to access the report externally, not set if the report is private */
            'shareable_link' => $this->resource->getShareableLink(),
            'properties' => [
                /** @var string $group Type of first grouping */
                'group' => $this->resource->properties->group->value,
                /** @var string $sub_group Type of second grouping */
                'sub_group' => $this->resource->properties->subGroup->value,
                /** @var string $history_group Type of grouping of the historic aggregation (time chart) */
                'history_group' => $this->resource->properties->historyGroup->value,
                /** @var string $start Start date of the report */
                'start' => $this->formatDateTime($this->resource->properties->start),
                /** @var string $end End date of the report */
                'end' => $this->formatDateTime($this->resource->properties->end),
                /** @var bool|null $active Whether the report is active */
                'active' => $this->resource->properties->active,
                /** @var array<string>|null $member_ids Filter by multiple member IDs, member IDs are OR combined */
                'member_ids' => $this->resource->properties->memberIds?->toArray(),
                /** @var bool|null $billable Filter by billable status */
                'billable' => $this->resource->properties->billable,
                /** @var array<string>|null $client_ids Filter by client IDs, client IDs are OR combined */
                'client_ids' => $this->resource->properties->clientIds?->toArray(),
                /** @var array<string>|null $project_ids Filter by project IDs, project IDs are OR combined */
                'project_ids' => $this->resource->properties->projectIds?->toArray(),
                /** @var array<string>|null $tags_ids Filter by tag IDs, tag IDs are OR combined */
                'tag_ids' => $this->resource->properties->tagIds?->toArray(),
                /** @var array<string>|null $task_ids Filter by task IDs, task IDs are OR combined */
                'task_ids' => $this->resource->properties->taskIds?->toArray(),
            ],
            /** @var string $created_at Date when the report was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** @var string $updated_at Date when the report was last updated */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
