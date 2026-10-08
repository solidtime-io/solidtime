<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Member;

use App\Http\Resources\V1\BaseResource;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @property Member $resource
 */
class MemberResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|array<string>>
     */
    public function toArray(Request $request): array
    {
        return [
            /** ID of membership */
            'id' => $this->resource->id,
            /** ID of user */
            'user_id' => $this->resource->user->id,
            /** Name */
            'name' => $this->resource->user->name,
            /** Email */
            'email' => $this->resource->user->email,
            /** Role */
            'role' => $this->resource->role,
            /** Placeholder user for imports, user might not really exist and does not know about this placeholder membership */
            'is_placeholder' => $this->resource->user->is_placeholder,
            /** Billable rate in cents per hour */
            'billable_rate' => $this->resource->billable_rate,
        ];
    }
}
