<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Passport;

use App\Models\Passport\Client;
use App\Models\Passport\Token;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Unit\Model\ModelTestAbstract;

#[CoversClass(Token::class)]
class TokenModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_client(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $token = Token::factory()->forClient($client)->create();

        // Act
        $token->refresh();
        $clientRel = $token->client;

        // Assert
        $this->assertNotNull($clientRel);
        $this->assertTrue($clientRel->is($client));
    }

    public function test_it_belongs_to_a_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $token = Token::factory()->forUser($user)->forClient($client)->create();

        // Act
        $token->refresh();
        $userRel = $token->user;

        // Assert
        $this->assertNotNull($userRel);
        $this->assertTrue($userRel->is($user));
    }

    public function test_scope_is_api_tokens_only_returns_api_tokens_with_no_parameters(): void
    {
        // Arrange
        $clientApi = Client::factory()->apiClient()->create();
        $clientDesktop = Client::factory()->desktopClient()->create();
        $token1 = Token::factory()->forClient($clientApi)->create();
        $token2 = Token::factory()->forClient($clientDesktop)->create();

        // Act
        $apiTokens = Token::query()
            ->isApiToken()
            ->get();

        // Assert
        $this->assertCount(1, $apiTokens);
        $this->assertTrue($apiTokens->first()->is($token1));
    }

    public function test_scope_is_api_tokens_only_returns_api_tokens_with_true(): void
    {
        // Arrange
        $clientApi = Client::factory()->apiClient()->create();
        $clientDesktop = Client::factory()->desktopClient()->create();
        $token1 = Token::factory()->forClient($clientApi)->create();
        $token2 = Token::factory()->forClient($clientDesktop)->create();

        // Act
        $apiTokens = Token::query()
            ->isApiToken(true)
            ->get();

        // Assert
        $this->assertCount(1, $apiTokens);
        $this->assertTrue($apiTokens->first()->is($token1));
    }

    public function test_scope_is_api_tokens_only_returns_api_tokens_with_false(): void
    {
        // Arrange
        $clientApi = Client::factory()->apiClient()->create();
        $clientDesktop = Client::factory()->desktopClient()->create();
        $token1 = Token::factory()->forClient($clientApi)->create();
        $token2 = Token::factory()->forClient($clientDesktop)->create();

        // Act
        $apiTokens = Token::query()
            ->isApiToken(false)
            ->get();

        // Assert
        $this->assertCount(1, $apiTokens);
        $this->assertTrue($apiTokens->first()->is($token2));
    }
}
