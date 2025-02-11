<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\ApiToken;

use App\Models\Passport\Token;
use Illuminate\Http\Request;

/**
 * @property-read Token $resource
 */
class ApiTokenWithAccessTokenResource extends ApiTokenResource
{
    private string $accessToken;

    public function __construct(Token $resource, string $accessToken)
    {
        $this->accessToken = $accessToken;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null|array<string>>
     */
    public function toArray(Request $request): array
    {
        $parent = parent::toArray($request);

        return $parent + [
            'access_token' => $this->accessToken,
        ];
    }
}
