<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Project;

use App\Http\Resources\V1\BaseResource;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * @property Project $resource
 */
class ProjectResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null>
     */
    public function toArray(Request $request): array
    {
        $limitedVisibility = is_bool($this->resource->getAttributeValue('limited_visibility')) ? $this->resource->getAttributeValue('limited_visibility') : true;

        return [
            /** @var string $id ID of project */
            'id' => $this->resource->id,
            /** @var string $name Name of project */
            'name' => $this->resource->name,
            /** @var string $color Color of project */
            'color' => $this->resource->color,
            /** @var string|null $client_id ID of client */
            'client_id' => $this->resource->client_id,
            /** @var bool $is_archived Whether the client is archived */
            'is_archived' => $this->resource->is_archived,
            /** @var int|null $billable_rate Billable rate in cents per hour */
            'billable_rate' => $limitedVisibility ? null : $this->resource->billable_rate,
            /** @var bool $is_billable Project time entries billable default */
            'is_billable' => $this->resource->is_billable,
            /** @var int|null $estimated_time Estimated time in seconds */
            'estimated_time' => $limitedVisibility ? null : $this->resource->estimated_time,
            /** @var int $spent_time Spent time on this project in seconds (sum of the duration of all associated time entries, excl. still running time entries) */
            'spent_time' => $limitedVisibility ? null : $this->resource->spent_time,
            /** @var bool $limited_visibility */
            'limited_visibility' => $limitedVisibility,
        ];
    }
}
