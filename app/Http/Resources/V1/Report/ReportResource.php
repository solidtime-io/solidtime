<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Report;

use App\Http\Resources\V1\BaseResource;
use App\Models\Report;
use Illuminate\Http\Request;

/**
 * @property Report $resource
 */
class ReportResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|array<string>>
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
            /** Date when the report was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** Date when the report was last updated */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
