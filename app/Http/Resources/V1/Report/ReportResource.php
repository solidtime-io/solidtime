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
            /** @var string $created_at Date when the report was created */
            'created_at' => $this->resource->created_at?->toIso8601ZuluString(),
            /** @var string $updated_at Date when the report was last updated */
            'updated_at' => $this->resource->updated_at?->toIso8601ZuluString(),
        ];
    }
}
