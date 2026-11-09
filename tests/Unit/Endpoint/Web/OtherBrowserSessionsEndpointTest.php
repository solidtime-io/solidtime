<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Http\Controllers\Web\OtherBrowserSessionsController;
use App\Models\Passport\Client;
use App\Models\Passport\RefreshToken;
use App\Models\Passport\Token;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(OtherBrowserSessionsController::class)]
class OtherBrowserSessionsEndpointTest extends EndpointTestAbstract
{
    public function test_destroy_logs_out_other_browser_sessions_with_the_correct_password(): void
    {
        // Arrange
        $user = User::factory()->create();
        $originalPasswordHash = $user->password;
        $this->actingAs($user);

        // Act
        $response = $this->delete('/user/other-browser-sessions', [
            'password' => 'password',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        // logoutOtherDevices re-hashes the password (same plaintext, new hash) to invalidate other sessions.
        $this->assertNotSame($originalPasswordHash, $user->fresh()->password);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_destroy_fails_with_an_incorrect_password(): void
    {
        // Arrange
        $user = User::factory()->create();
        $originalPasswordHash = $user->password;
        $this->actingAs($user);

        // Act
        $response = $this->delete('/user/other-browser-sessions', [
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertSessionHasErrors('password');
        // No side effects when the password is incorrect: the password must not be re-hashed.
        $this->assertSame($originalPasswordHash, $user->fresh()->password);
    }

    public function test_destroy_requires_authentication(): void
    {
        // Act
        $response = $this->delete('/user/other-browser-sessions', [
            'password' => 'password',
        ]);

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function test_destroy_deletes_the_other_database_session_records_of_the_current_user(): void
    {
        // Arrange
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        DB::table('sessions')->insert([
            [
                'id' => 'other-session-of-current-user',
                'user_id' => $user->getKey(),
                'ip_address' => '192.0.2.10',
                'user_agent' => '',
                'payload' => '',
                'last_activity' => now()->subMinutes(5)->timestamp,
            ],
            [
                'id' => 'session-of-another-user',
                'user_id' => $otherUser->getKey(),
                'ip_address' => '192.0.2.30',
                'user_agent' => '',
                'payload' => '',
                'last_activity' => now()->timestamp,
            ],
        ]);

        // Act
        $response = $this->delete('/user/other-browser-sessions', [
            'password' => 'password',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        // The current user's other sessions are removed, while another user's session is untouched.
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-of-current-user']);
        $this->assertDatabaseHas('sessions', ['id' => 'session-of-another-user']);
    }

    public function test_destroy_revokes_app_tokens_but_not_personal_access_tokens(): void
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
        $this->actingAs($user);

        // Act
        $response = $this->delete('/user/other-browser-sessions', [
            'password' => 'password',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertTrue($desktopToken->fresh()->revoked);
        $this->assertTrue(RefreshToken::query()->findOrFail('desktop-refresh-token')->revoked);
        $this->assertFalse($personalAccessToken->fresh()->revoked);
        $this->assertFalse($otherUserToken->fresh()->revoked);
    }

    public function test_destroy_does_not_revoke_app_tokens_with_an_incorrect_password(): void
    {
        // Arrange
        $user = User::factory()->create();
        $desktopClient = Client::factory()->desktopClient()->create();
        $desktopToken = Token::factory()->forUser($user)->forClient($desktopClient)->create([
            'expires_at' => now()->addDay(),
        ]);
        $this->actingAs($user);

        // Act
        $response = $this->delete('/user/other-browser-sessions', [
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertSessionHasErrors('password');
        $this->assertFalse($desktopToken->fresh()->revoked);
    }
}
