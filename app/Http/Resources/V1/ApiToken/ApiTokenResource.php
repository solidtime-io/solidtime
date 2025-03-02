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
            /** @var string $id ID of the API token, this ID is NOT a UUID */
            'id' => $this->resource->id,
            /** @var string $name Name of the API token */
            'name' => $this->resource->name,
            /** @var bool $revoked Whether the API token is revoked */
            'revoked' => $this->resource->revoked,
            /** @var array<string> $scopes List of scopes that the API token has */
            'scopes' => $this->resource->scopes,
            /** @var string $created_at When the API token was created (ISO 8601 format, UTC timezone, example: 2024-02-26T17:17:17Z) */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** @var string|null $expires_at At what time the API token expires (ISO 8601 format, UTC timezone, example: 2024-02-26T17:17:17Z) */
            'expires_at' => $this->formatDateTime($this->resource->expires_at),
        ];
    }
}
