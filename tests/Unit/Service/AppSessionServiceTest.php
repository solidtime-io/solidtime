<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\Passport\Client;
use App\Models\Passport\RefreshToken;
use App\Models\Passport\Token;
use App\Models\User;
use App\Service\AppSessionService;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(AppSessionService::class)]
class AppSessionServiceTest extends TestCaseWithDatabase
{
    public function test_revoke_app_sessions_revokes_app_tokens_and_refresh_tokens_of_the_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $desktopClient = Client::factory()->desktopClient()->create();
        $personalAccessClient = Client::factory()->personalAccessClient()->create();
        $desktopToken = Token::factory()->forUser($user)->forClient($desktopClient)->create([
            'expires_at' => now()->addDay(),
        ]);
        RefreshToken::query()->forceCreate([
            'id' => 'desktop-refresh-token',
            'access_token_id' => $desktopToken->id,
            'revoked' => false,
            'expires_at' => now()->addMonth(),
        ]);
        $personalAccessToken = Token::factory()->forUser($user)->forClient($personalAccessClient)->create([
            'expires_at' => now()->addDay(),
        ]);
        $otherUserToken = Token::factory()->forUser($otherUser)->forClient($desktopClient)->create([
            'expires_at' => now()->addDay(),
        ]);

        // Act
        app(AppSessionService::class)->revokeAppSessions($user);

        // Assert
        $this->assertTrue($desktopToken->fresh()->revoked);
        $this->assertTrue(RefreshToken::query()->findOrFail('desktop-refresh-token')->revoked);
        $this->assertFalse($personalAccessToken->fresh()->revoked);
        $this->assertFalse($otherUserToken->fresh()->revoked);
    }
}
