<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Invitation;

use App\Http\Resources\V1\BaseResource;
use App\Models\OrganizationInvitation;
use Illuminate\Http\Request;

/**
 * @property OrganizationInvitation $resource
 */
class InvitationResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|array<string>>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID of the invitation */
            'id' => $this->resource->id,
            /** @var string $email Email */
            'user_id' => $this->resource->email,
            /** @var string $role Role */
            'name' => $this->resource->role,
        ];
    }
}
