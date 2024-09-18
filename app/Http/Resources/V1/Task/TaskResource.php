<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Task;

use App\Http\Resources\V1\BaseResource;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Http\Request;

/**
 * @property Task $resource
 */
class TaskResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID */
            'id' => $this->resource->id,
            /** @var string $name Name */
            'name' => $this->resource->name,
            /** @var bool $is_done Whether the task is done */
            'is_done' => $this->resource->is_done,
            /** @var string $project_id ID of the project */
            'project_id' => $this->resource->project_id,
            /** @var int|null $estimated_time Estimated time in seconds */
            'estimated_time' => $this->resource->estimated_time,
            /** @var int $spent_time Spent time on this task in seconds (sum of the duration of all associated time entries, excl. still running time entries) */
            'spent_time' => $this->resource->spent_time,
            /** @var string $created_at When the tag was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** @var string $updated_at When the tag was last updated */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
