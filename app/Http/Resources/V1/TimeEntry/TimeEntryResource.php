<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\TimeEntry;

use App\Http\Resources\V1\BaseResource;
use App\Models\TimeEntry;
use Illuminate\Http\Request;

/**
 * @property TimeEntry $resource
 */
class TimeEntryResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|array<string>>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID of time entry */
            'id' => $this->resource->id,
            /**
             * @var string $start Start of time entry (ISO 8601 format, UTC timezone, example: 2024-02-26T17:17:17Z)
             */
            'start' => $this->formatDateTime($this->resource->start),
            /**
             * @var string|null $end End of time entry (ISO 8601 format, UTC timezone, example: 2024-02-26T17:17:17Z)
             */
            'end' => $this->formatDateTime($this->resource->end),
            /** @var int|null $duration Duration of time entry in seconds */
            'duration' => $this->resource->getDuration()?->seconds,
            /** @var string|null $description Description of time entry */
            'description' => $this->resource->description,
            /** @var string|null $task_id ID of task */
            'task_id' => $this->resource->task_id,
            /** @var string|null $project_id ID of project */
            'project_id' => $this->resource->project_id,
            /** @var string $user_id ID of user */
            'user_id' => $this->resource->user_id,
            /** @var array<string> $tags List of tag IDs */
            'tags' => $this->resource->tags ?? [],
        ];
    }
}
