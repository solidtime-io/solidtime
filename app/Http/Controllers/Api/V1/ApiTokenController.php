<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\PersonalAccessClientIsNotConfiguredException;
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

        $tokens = $user->tokens()
            ->where('client_id', '=', config('passport.personal_access_client.id'))
            ->get();

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
     * @throws AuthorizationException|PersonalAccessClientIsNotConfiguredException
     */
    public function store(ApiTokenStoreRequest $request): ApiTokenWithAccessTokenResource
    {
        $user = $this->user();

        if (config('passport.personal_access_client.id') === null || config('passport.personal_access_client.secret') === null) {
            throw new PersonalAccessClientIsNotConfiguredException;
        }

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
     * @throws PersonalAccessClientIsNotConfiguredException
     */
    public function revoke(Token $apiToken): JsonResponse
    {
        $user = $this->user();

        if (config('passport.personal_access_client.id') === null || config('passport.personal_access_client.secret') === null) {
            throw new PersonalAccessClientIsNotConfiguredException;
        }
        if ($apiToken->user_id !== $user->getKey()) {
            throw new AuthorizationException('API token does not belong to user');
        }
        if ($apiToken->client_id !== config('passport.personal_access_client.id')) {
            throw new AuthorizationException('API token is not a personal access token');
        }

        $apiToken->revoke();

        return response()->json(null, 204);
    }

    /**
     * Delete an api token
     *
     * @operationId deleteApiToken
     *
     * @throws AuthorizationException|PersonalAccessClientIsNotConfiguredException
     */
    public function destroy(Token $apiToken): JsonResponse
    {
        $user = $this->user();

        if (config('passport.personal_access_client.id') === null || config('passport.personal_access_client.secret') === null) {
            throw new PersonalAccessClientIsNotConfiguredException;
        }
        if ($apiToken->user_id !== $user->getKey()) {
            throw new AuthorizationException('API token does not belong to user');
        }
        if ($apiToken->client_id !== config('passport.personal_access_client.id')) {
            throw new AuthorizationException('API token is not a personal access token');
        }

        $apiToken->delete();

        return response()->json(null, 204);
    }
}
