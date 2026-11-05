<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Models\Passport\Client;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Client::class)]
class OAuthTokenEndpointTest extends EndpointTestAbstract
{
    public function test_token_endpoint_returns_invalid_client_for_client_id_that_is_not_a_uuid(): void
    {
        // Act
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'audit',
            'client_secret' => 'secret',
        ]);

        // Assert
        $response->assertStatus(401);
        $response->assertJsonPath('error', 'invalid_client');
    }

    public function test_device_authorization_grant_routes_are_not_registered(): void
    {
        // Act
        $userCodeResponse = $this->get('/oauth/device');
        $deviceCodeResponse = $this->postJson('/oauth/device/code', [
            'client_id' => 'audit',
        ]);

        // Assert
        $userCodeResponse->assertNotFound();
        $deviceCodeResponse->assertNotFound();
    }

    public function test_token_endpoint_rejects_device_code_grant(): void
    {
        // Act
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => 'audit',
            'device_code' => 'code',
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('error', 'unsupported_grant_type');
    }
}
