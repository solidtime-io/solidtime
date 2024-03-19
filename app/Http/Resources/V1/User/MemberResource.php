<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\User;

use App\Http\Resources\V1\BaseResource;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @property User $resource
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
        /** @var Membership $membership */
        $membership = $this->resource->getRelationValue('membership');

        return [
            /** @var string $id ID */
            'id' => $this->resource->id,
            /** @var string $name Name */
            'name' => $this->resource->name,
            /** @var string $email Email */
            'email' => $this->resource->email,
            /** @var string $role Role */
            'role' => $membership->role,
            /** @var bool $is_placeholder Placeholder user for imports, user might not really exist and does not know about this placeholder membership */
            'is_placeholder' => $this->resource->is_placeholder,
        ];
    }
}
