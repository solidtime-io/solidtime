<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Member;

use App\Http\Resources\V1\BaseResource;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @property Membership $resource
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
            /** @var string $id ID of membership */
            'id' => $this->resource->id,
            /** @var string $id ID of user */
            'user_id' => $this->resource->user->id,
            /** @var string $name Name */
            'name' => $this->resource->user->name,
            /** @var string $email Email */
            'email' => $this->resource->user->email,
            /** @var string $role Role */
            'role' => $this->resource->role,
            /** @var bool $is_placeholder Placeholder user for imports, user might not really exist and does not know about this placeholder membership */
            'is_placeholder' => $this->resource->user->is_placeholder,
            /** @var int|null $billable_rate Billable rate in cents per hour */
            'billable_rate' => $this->resource->billable_rate,
        ];
    }
}
