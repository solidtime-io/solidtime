<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\TimeEntry;

use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TimeEntry $resource
 */
class TimeEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|boolean|integer>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID of time entry */
            'id' => $this->resource->id,
        ];
    }
}
