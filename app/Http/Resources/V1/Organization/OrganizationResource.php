<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Organization;

use App\Http\Resources\V1\BaseResource;
use App\Models\Organization;
use Illuminate\Http\Request;

/**
 * @property Organization $resource
 */
class OrganizationResource extends BaseResource
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
            /** @var bool $color Personal organizations automatically created after registration */
            'is_personal' => $this->resource->personal_team,
            /** @var int|null $billable_rate Billable rate in cents per hour */
            'billable_rate' => $this->resource->billable_rate,
        ];
    }
}
