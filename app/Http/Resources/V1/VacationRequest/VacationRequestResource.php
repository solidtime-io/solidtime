<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\VacationRequest;

use App\Http\Resources\V1\BaseResource;
use App\Models\VacationRequest;
use Illuminate\Http\Request;

/**
 * @property VacationRequest $resource
 */
class VacationRequestResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'organization_id' => $this->resource->organization_id,
            'member_id' => $this->resource->member_id,
            'member_name' => $this->resource->member?->user?->name,
            'type' => $this->resource->type->value,
            'start_date' => $this->formatDate($this->resource->start_date),
            'end_date' => $this->formatDate($this->resource->end_date),
            'half_day' => $this->resource->half_day,
            'days_count' => $this->resource->days_count,
            'status' => $this->resource->status->value,
            'private_note' => $this->resource->private_note,
            'public_note' => $this->resource->public_note,
            'reviewed_by' => $this->resource->reviewed_by,
            'reviewer_name' => $this->resource->reviewer?->user?->name,
            'reviewed_at' => $this->formatDateTime($this->resource->reviewed_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
