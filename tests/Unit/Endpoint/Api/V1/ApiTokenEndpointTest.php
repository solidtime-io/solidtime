<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\ApiTokenController;
use App\Models\Passport\Client;
use App\Models\Passport\Token;
use Illuminate\Support\Facades\Config;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(ApiTokenController::class)]
class ApiTokenEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_returns_list_api_tokens(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        $personalAccessClient = $this->createPersonalAccessClient();
        Config::set('passport.personal_access_client.id', $personalAccessClient->id);
        Config::set('passport.personal_access_client.secret', $personalAccessClient->secret);
        $client = $this->createClient();
        $token = Token::factory()->forUser($data->user)->forClient($personalAccessClient)->create();
        $otherTokenType = Token::factory()->forUser($data->user)->forClient($client)->create();
        $otherData = $this->createUserWithPermission([]);
        $otherToken = Token::factory()->forUser($otherData->user)->forClient($personalAccessClient)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.api-tokens.index'));

        // Assert
        $this->assertResponseCode($response, 200);
        $response->assertExactJson([
            'data' => [
                [
                    'id' => $token->id,
                    'name' => $token->name,
                    'scopes' => $token->scopes,
                    'revoked' => $token->revoked,
                    'created_at' => $token->created_at->toIso8601ZuluString(),
                    'expires_at' => $token->expires_at->toIso8601ZuluString(),
                ],
            ],
        ]);
    }

    public function test_store_endpoint_creates_new_api_token(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        $personalAccessClient = $this->createPersonalAccessClient();
        Config::set('passport.personal_access_client.id', $personalAccessClient->id);
        Config::set('passport.personal_access_client.secret', $personalAccessClient->secret);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.api-tokens.store'), [
            'name' => 'Test Token',
        ]);

        // Assert
        $this->assertResponseCode($response, 200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'scopes',
                'revoked',
                'created_at',
                'expires_at',
                'access_token',
            ],
        ]);
    }

    public function test_store_fails_if_personal_access_client_is_not_configured(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.api-tokens.store'), [
            'name' => 'Test Token',
        ]);

        // Assert
        $this->assertResponseCode($response, 400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'personal_access_client_is_not_configured',
            'message' => 'Personal access client is not configured',
        ]);
    }

    public function test_revoke_endpoint_revokes_api_token(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        $client = $this->createPersonalAccessClient();
        Config::set('passport.personal_access_client.id', $client->id);
        Config::set('passport.personal_access_client.secret', $client->secret);
        $token = Token::factory()->forUser($data->user)->forClient($client)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.api-tokens.revoke', $token->id));

        // Assert
        $this->assertResponseCode($response, 204);
        $this->assertDatabaseHas(Token::class, [
            'id' => $token->id,
            'revoked' => true,
        ]);
    }

    public function test_revoke_fails_if_token_is_not_personal_access_token(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        $personalAccessClient = $this->createPersonalAccessClient();
        Config::set('passport.personal_access_client.id', $personalAccessClient->id);
        Config::set('passport.personal_access_client.secret', $personalAccessClient->secret);
        $client = $this->createClient();
        $token = Token::factory()->forUser($data->user)->forClient($client)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.api-tokens.revoke', $token->id));

        // Assert
        $this->assertResponseCode($response, 403);
        $this->assertDatabaseHas(Token::class, [
            'id' => $token->id,
            'revoked' => false,
        ]);
    }

    public function test_revoke_fails_if_token_with_id_does_not_exist(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.api-tokens.revoke', 'not-valid'));

        // Assert
        $this->assertResponseCode($response, 404);
    }

    public function test_revoke_fails_if_personal_access_client_is_not_configured(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        $client = $this->createPersonalAccessClient();
        $token = Token::factory()->forUser($data->user)->forClient($client)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.api-tokens.revoke', $token->id));

        // Assert
        $this->assertResponseCode($response, 400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'personal_access_client_is_not_configured',
            'message' => 'Personal access client is not configured',
        ]);
    }

    public function test_revoke_fails_if_the_token_does_not_belong_to_the_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        $otherData = $this->createUserWithPermission([]);
        $client = $this->createPersonalAccessClient();
        Config::set('passport.personal_access_client.id', $client->id);
        Config::set('passport.personal_access_client.secret', $client->secret);
        $token = Token::factory()->forUser($otherData->user)->forClient($client)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.api-tokens.revoke', $token->id));

        // Assert
        $this->assertResponseCode($response, 403);
        $this->assertDatabaseHas(Token::class, [
            'id' => $token->id,
            'revoked' => false,
        ]);
    }

    public function test_destroy_endpoint_deletes_api_token(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        $client = $this->createPersonalAccessClient();
        Config::set('passport.personal_access_client.id', $client->id);
        Config::set('passport.personal_access_client.secret', $client->secret);
        $token = Token::factory()->forUser($data->user)->forClient($client)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.api-tokens.destroy', $token->id));

        // Assert
        $this->assertResponseCode($response, 204);
        $this->assertDatabaseMissing(Token::class, ['id' => $token->id]);
    }

    public function test_destroy_fails_if_personal_access_client_is_not_configured(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        $client = $this->createPersonalAccessClient();
        $token = Token::factory()->forUser($data->user)->forClient($client)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.api-tokens.destroy', $token->id));

        // Assert
        $this->assertResponseCode($response, 400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'personal_access_client_is_not_configured',
            'message' => 'Personal access client is not configured',
        ]);
    }

    public function test_destroy_fails_if_token_is_not_personal_access_token(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        $personalAccessClient = $this->createPersonalAccessClient();
        Config::set('passport.personal_access_client.id', $personalAccessClient->id);
        Config::set('passport.personal_access_client.secret', $personalAccessClient->secret);
        $client = $this->createClient();
        $token = Token::factory()->forUser($data->user)->forClient($client)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.api-tokens.destroy', $token->id));

        // Assert
        $this->assertResponseCode($response, 403);
        $this->assertDatabaseHas(Token::class, [
            'id' => $token->id,
        ]);
    }

    public function test_destroy_fails_if_token_with_id_does_not_exist(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.api-tokens.destroy', 'not-valid'));

        // Assert
        $this->assertResponseCode($response, 404);
    }

    public function test_destroy_fails_if_the_token_does_not_belong_to_the_user(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([]);
        $otherData = $this->createUserWithPermission([]);
        $client = $this->createPersonalAccessClient();
        Config::set('passport.personal_access_client.id', $client->id);
        Config::set('passport.personal_access_client.secret', $client->secret);
        $token = Token::factory()->forUser($otherData->user)->forClient($client)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.api-tokens.destroy', $token->id));

        // Assert
        $this->assertResponseCode($response, 403);
        $this->assertDatabaseHas(Token::class, [
            'id' => $token->id,
        ]);
    }

    private function createPersonalAccessClient(): Client
    {
        $clientRepository = new ClientRepository;
        /** @var Client $client */
        $client = $clientRepository->createPersonalAccessClient(
            null, 'Test Personal Access Client', 'http://localhost'
        );

        return $client;
    }

    private function createClient(): Client
    {
        $clientRepository = new ClientRepository;
        /** @var Client $client */
        $client = $clientRepository->create(
            null, 'Desktop App', 'http://localhost', null
        );

        return $client;
    }
}
