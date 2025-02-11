<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\ApiToken;

use App\Http\Resources\V1\BaseResource;
use App\Models\Passport\Token;
use Illuminate\Http\Request;

/**
 * @property-read Token $resource
 */
class ApiTokenResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|array<string>>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'revoked' => $this->resource->revoked,
            'scopes' => $this->resource->scopes,
            'created_at' => $this->formatDateTime($this->resource->created_at),
            'expires_at' => $this->formatDateTime($this->resource->expires_at),
        ];
    }
}
