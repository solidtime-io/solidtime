<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\ProjectMember;

use App\Http\Resources\V1\BaseResource;
use App\Models\ProjectMember;
use Illuminate\Http\Request;

/**
 * @property ProjectMember $resource
 */
class ProjectMemberResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID of project member */
            'id' => $this->resource->id,
            /** @var int|null $billable_rate Billable rate in cents per hour */
            'billable_rate' => $this->resource->billable_rate,
            /** @var string $member_id ID of the organization member */
            'member_id' => $this->resource->member_id,
            /** @var string $project_id ID of the project */
            'project_id' => $this->resource->project_id,
        ];
    }
}
