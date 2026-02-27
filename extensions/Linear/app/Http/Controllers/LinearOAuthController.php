<?php

declare(strict_types=1);

namespace Extensions\Linear\Http\Controllers;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Organization;
use Extensions\Linear\Models\LinearIntegration;
use Extensions\Linear\Services\LinearGraphQLClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LinearOAuthController extends Controller
{
    private const AUTH_URL = 'https://linear.app/oauth/authorize';

    private const TOKEN_URL = 'https://api.linear.app/oauth/token';

    public function connect(Organization $organization, Request $request): JsonResponse
    {
        $this->checkPermission($organization, 'linear:manage');

        $state = Str::random(40);
        session(['linear_oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => config('linear.client_id'),
            'redirect_uri' => route('api.v1.linear.callback', ['organization' => $organization->getKey()]),
            'response_type' => 'code',
            'scope' => 'read',
            'state' => $state,
            'actor' => 'user',
        ]);

        return new JsonResponse([
            'redirect_url' => self::AUTH_URL.'?'.$params,
        ]);
    }

    public function callback(Organization $organization, Request $request): JsonResponse
    {
        $this->checkPermission($organization, 'linear:manage');

        $tokenResponse = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'code' => $request->input('code'),
            'client_id' => config('linear.client_id'),
            'client_secret' => config('linear.client_secret'),
            'redirect_uri' => route('api.v1.linear.callback', ['organization' => $organization->getKey()]),
        ]);

        $tokenData = $tokenResponse->json();
        $accessToken = $tokenData['access_token'];
        $expiresIn = $tokenData['expires_in'] ?? 86400;

        $client = new LinearGraphQLClient($accessToken);
        $viewer = $client->query('{ viewer { id name } }');

        LinearIntegration::updateOrCreate(
            [
                'user_id' => $this->user()->getKey(),
                'organization_id' => $organization->getKey(),
            ],
            [
                'access_token' => $accessToken,
                'refresh_token' => $tokenData['refresh_token'] ?? '',
                'token_expires_at' => now()->addSeconds($expiresIn),
                'linear_user_id' => $viewer['viewer']['id'],
            ]
        );

        return new JsonResponse([
            'message' => 'Connected to Linear',
            'linear_user' => $viewer['viewer']['name'],
        ]);
    }

    public function status(Organization $organization): JsonResponse
    {
        $this->checkPermission($organization, 'linear:manage');

        $integration = LinearIntegration::where('user_id', $this->user()->getKey())
            ->where('organization_id', $organization->getKey())
            ->first();

        if ($integration === null) {
            return new JsonResponse(['connected' => false]);
        }

        return new JsonResponse([
            'connected' => true,
            'linear_user_id' => $integration->linear_user_id,
            'last_synced_at' => $integration->last_synced_at?->toIso8601String(),
        ]);
    }

    public function disconnect(Organization $organization): JsonResponse
    {
        $this->checkPermission($organization, 'linear:manage');

        $integration = LinearIntegration::where('user_id', $this->user()->getKey())
            ->where('organization_id', $organization->getKey())
            ->first();

        if ($integration !== null) {
            Http::asForm()->post('https://api.linear.app/oauth/revoke', [
                'client_id' => config('linear.client_id'),
                'client_secret' => config('linear.client_secret'),
                'token' => $integration->access_token,
            ]);

            $integration->delete();
        }

        return new JsonResponse(null, 204);
    }
}
