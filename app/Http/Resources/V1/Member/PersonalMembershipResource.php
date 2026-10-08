<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Member;

use App\Http\Resources\V1\BaseResource;
use App\Models\Member;
use Illuminate\Http\Request;

/**
 * @property Member $resource
 */
class PersonalMembershipResource extends BaseResource
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
            'organization' => [
                /** ID of organization */
                'id' => $this->resource->organization->id,
                /** Name of organization */
                'name' => $this->resource->organization->name,
                /** Currency code (ISO 4217) of organization */
                'currency' => $this->resource->organization->currency,
            ],
            /** Role */
            'role' => $this->resource->role,
        ];
    }
}
