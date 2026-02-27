<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Controllers;

use Extensions\Linear\Http\Controllers\LinearOAuthController;
use Extensions\Linear\Models\LinearIntegration;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(LinearOAuthController::class)]
class LinearOAuthControllerTest extends TestCaseWithDatabase
{
    public function test_connect_returns_redirect_url_to_linear_oauth(): void
    {
        $data = $this->createUserWithPermission(['linear:manage']);
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.linear.connect', [
            'organization' => $data->organization->getKey(),
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['redirect_url']);
        $this->assertStringContainsString('linear.app/oauth/authorize', $response->json('redirect_url'));
    }

    public function test_callback_exchanges_code_and_stores_integration(): void
    {
        $data = $this->createUserWithPermission(['linear:manage']);
        Passport::actingAs($data->user);

        Http::fake([
            'api.linear.app/oauth/token' => Http::response([
                'access_token' => 'lin_access_123',
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'scope' => ['read'],
            ], 200),
            'api.linear.app/graphql' => Http::response([
                'data' => ['viewer' => ['id' => 'linear-user-abc', 'name' => 'Test User']],
            ], 200),
        ]);

        $response = $this->getJson(route('api.v1.linear.callback', [
            'organization' => $data->organization->getKey(),
            'code' => 'auth-code-123',
        ]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('linear_integrations', [
            'user_id' => $data->user->getKey(),
            'organization_id' => $data->organization->getKey(),
            'linear_user_id' => 'linear-user-abc',
        ]);
    }

    public function test_status_returns_connected_when_integration_exists(): void
    {
        $data = $this->createUserWithPermission(['linear:manage']);
        Passport::actingAs($data->user);

        LinearIntegration::create([
            'user_id' => $data->user->getKey(),
            'organization_id' => $data->organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'user-123',
        ]);

        $response = $this->getJson(route('api.v1.linear.status', [
            'organization' => $data->organization->getKey(),
        ]));

        $response->assertStatus(200);
        $response->assertJson(['connected' => true]);
    }

    public function test_status_returns_not_connected_when_no_integration(): void
    {
        $data = $this->createUserWithPermission(['linear:manage']);
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.linear.status', [
            'organization' => $data->organization->getKey(),
        ]));

        $response->assertStatus(200);
        $response->assertJson(['connected' => false]);
    }

    public function test_disconnect_removes_integration(): void
    {
        $data = $this->createUserWithPermission(['linear:manage']);
        Passport::actingAs($data->user);

        LinearIntegration::create([
            'user_id' => $data->user->getKey(),
            'organization_id' => $data->organization->getKey(),
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'token_expires_at' => now()->addDay(),
            'linear_user_id' => 'user-123',
        ]);

        Http::fake([
            'api.linear.app/oauth/revoke' => Http::response(null, 200),
        ]);

        $response = $this->deleteJson(route('api.v1.linear.disconnect', [
            'organization' => $data->organization->getKey(),
        ]));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('linear_integrations', [
            'user_id' => $data->user->getKey(),
        ]);
    }
}
