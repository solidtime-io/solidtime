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
            'public_until' => $this->resource->public_until?->toIso8601ZuluString(),
            /** @var string|null $shareable_link Get link to access the report externally, not set if the report is private */
            'shareable_link' => $this->resource->getShareableLink(),
            'properties' => [
                'group' => $this->resource->properties->group->value,
                'sub_group' => $this->resource->properties->subGroup->value,
                /** @var string|null $start Start date of the report */
                'start' => $this->resource->properties->start?->toIso8601ZuluString(),
                /** @var string|null $end End date of the report */
                'end' => $this->resource->properties->end?->toIso8601ZuluString(),
                /** @var bool|null $active Whether the report is active */
                'active' => $this->resource->properties->active,
                'member_ids' => $this->resource->properties->memberIds?->toArray(),
                'billable' => $this->resource->properties->billable,
                'client_ids' => $this->resource->properties->clientIds?->toArray(),
                'project_ids' => $this->resource->properties->projectIds?->toArray(),
                'tag_ids' => $this->resource->properties->tagIds?->toArray(),
                'task_ids' => $this->resource->properties->taskIds?->toArray(),
            ],
        ];
    }
}
