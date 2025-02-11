<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\ApiToken\ApiTokenStoreRequest;
use App\Http\Resources\V1\ApiToken\ApiTokenCollection;
use App\Http\Resources\V1\ApiToken\ApiTokenWithAccessTokenResource;
use App\Models\Passport\Token;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class ApiTokenController extends Controller
{
    /**
     * List all api token of the currently authenticated user
     *
     * This endpoint is independent of organization.
     *
     * @operationId getApiTokens
     *
     * @throws AuthorizationException
     */
    public function index(): ApiTokenCollection
    {
        $user = $this->user();

        $tokens = $user->tokens()->get();

        return new ApiTokenCollection($tokens);
    }

    /**
     * Create a new api token for the currently authenticated user
     *
     * The response will contain the access token that can be used to send authenticated API requests.
     * Please note that the access token is only shown in this response and cannot be retrieved later.
     *
     * @operationId createApiToken
     *
     * @throws AuthorizationException
     */
    public function store(ApiTokenStoreRequest $request): ApiTokenWithAccessTokenResource
    {
        $user = $this->user();

        $token = $user->createToken($request->getName(), ['*']);
        /** @var Token $tokenModel */
        $tokenModel = $token->token;

        return new ApiTokenWithAccessTokenResource($tokenModel, $token->accessToken);
    }

    /**
     * Revoke an api token
     *
     * @operationId revokeApiToken
     *
     * @throws AuthorizationException
     */
    public function revoke(string $apiTokenId): JsonResponse
    {
        $user = $this->user();

        $apiToken = $user->tokens()->where('id', $apiTokenId)->firstOrFail();

        $apiToken->revoke();

        return response()->json(null, 204);
    }

    /**
     * Delete an api token
     *
     * @operationId deleteApiToken
     *
     * @throws AuthorizationException
     */
    public function destroy(string $apiTokenId): JsonResponse
    {
        $user = $this->user();

        $apiToken = $user->tokens()->where('id', $apiTokenId)->firstOrFail();

        $apiToken->delete();

        return response()->json(null, 204);
    }
}
