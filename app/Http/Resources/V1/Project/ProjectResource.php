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
    private bool $showBillableRate;

    public function __construct(Project $resource, bool $showBillableRate)
    {
        parent::__construct($resource);

        $this->showBillableRate = $showBillableRate;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null>
     */
    public function toArray(Request $request): array
    {
        return [
            /** ID of project */
            'id' => $this->resource->id,
            /** Name of project */
            'name' => $this->resource->name,
            /** Color of project */
            'color' => $this->resource->color,
            /** @var string|null $client_id ID of client */
            'client_id' => $this->resource->client_id,
            /** Whether the client is archived */
            'is_archived' => $this->resource->is_archived,
            /** Billable rate in cents per hour */
            'billable_rate' => $this->showBillableRate ? $this->resource->billable_rate : null,
            /** Project time entries billable default */
            'is_billable' => $this->resource->is_billable,
            /** Estimated time in seconds */
            'estimated_time' => $this->resource->estimated_time,
            /** Spent time on this project in seconds (sum of the duration of all associated time entries, excl. still running time entries) */
            'spent_time' => $this->resource->spent_time,
            /** Whether the project is public */
            'is_public' => $this->resource->is_public,
        ];
    }
}
