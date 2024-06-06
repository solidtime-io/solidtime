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
        return [
            /** @var string $id ID of project */
            'id' => $this->resource->id,
            /** @var string $name Name of project */
            'name' => $this->resource->name,
            /** @var string $color Color of project */
            'color' => $this->resource->color,
            /** @var string|null $client_id ID of client */
            'client_id' => $this->resource->client_id,
            /** @var int|null $billable_rate Billable rate in cents per hour */
            'billable_rate' => $this->resource->billable_rate,
            /** @var bool $is_billable Project time entries billable default */
            'is_billable' => $this->resource->is_billable,
        ];
    }
}
