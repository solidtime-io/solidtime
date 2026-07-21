<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Http\Controllers\Web\GoogleCalendarConnectionController;
use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GoogleCalendarConnectionController::class)]
class GoogleCalendarConnectionEndpointTest extends EndpointTestAbstract
{
    private function configureGoogle(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'https://solidtime.test/auth/google/callback',
        ]);
    }

    private function mockSocialiteCallback(string $email, ?string $refreshToken, ?string $accessToken = 'access-token'): void
    {
        $googleUser = new SocialiteUser;
        $googleUser->map([
            'id' => 'google-id-1',
            'email' => $email,
            'name' => 'Test User',
        ]);
        $googleUser->token = $accessToken;
        $googleUser->refreshToken = $refreshToken;
        $googleUser->expiresIn = 3599;

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_connect_returns_not_found_if_google_is_not_configured(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get('/settings/google-calendar/connect');

        // Assert
        $response->assertNotFound();
    }

    public function test_connect_redirects_to_google_with_offline_access_and_calendar_scope(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get('/settings/google-calendar/connect');

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth', $location);
        $this->assertStringContainsString('access_type=offline', $location);
        $this->assertStringContainsString('prompt=consent', $location);
        $this->assertStringContainsString('calendar.events.readonly', urldecode($location));
    }

    public function test_connect_requires_authentication(): void
    {
        // Arrange
        $this->configureGoogle();

        // Act
        $response = $this->get('/settings/google-calendar/connect');

        // Assert
        $response->assertRedirect('/login');
    }

    public function test_callback_stores_connection_for_current_user(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();
        $this->mockSocialiteCallback('calendar@example.com', 'refresh-token-1');

        // Act
        $response = $this->actingAs($user)->get('/settings/google-calendar/callback');

        // Assert
        $response->assertRedirect('/calendar');
        /** @var GoogleCalendarConnection|null $connection */
        $connection = GoogleCalendarConnection::query()->where('user_id', '=', $user->getKey())->first();
        $this->assertNotNull($connection);
        $this->assertSame('calendar@example.com', $connection->google_email);
        $this->assertSame('refresh-token-1', $connection->refresh_token);
        $this->assertSame('access-token', $connection->access_token);
        $this->assertNotNull($connection->access_token_expires_at);
    }

    public function test_callback_keeps_stored_refresh_token_if_google_does_not_send_a_new_one_for_the_same_account(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();
        GoogleCalendarConnection::factory()->forUser($user)->create([
            'google_email' => 'calendar@example.com',
            'refresh_token' => 'stored-refresh-token',
        ]);
        $this->mockSocialiteCallback('calendar@example.com', null);

        // Act
        $response = $this->actingAs($user)->get('/settings/google-calendar/callback');

        // Assert
        $response->assertRedirect('/calendar');
        /** @var GoogleCalendarConnection|null $connection */
        $connection = GoogleCalendarConnection::query()->where('user_id', '=', $user->getKey())->first();
        $this->assertNotNull($connection);
        $this->assertSame('stored-refresh-token', $connection->refresh_token);
    }

    public function test_callback_fails_if_google_does_not_send_a_refresh_token_for_a_new_connection(): void
    {
        // Arrange
        $this->configureGoogle();
        $user = User::factory()->create();
        $this->mockSocialiteCallback('calendar@example.com', null);

        // Act
        $response = $this->actingAs($user)->get('/settings/google-calendar/callback');

        // Assert
        $response->assertRedirect('/user/profile');
        $this->assertSame(0, GoogleCalendarConnection::query()->where('user_id', '=', $user->getKey())->count());
    }

    public function test_disconnect_revokes_and_deletes_the_connection(): void
    {
        // Arrange
        $user = User::factory()->create();
        GoogleCalendarConnection::factory()->forUser($user)->create();
        Http::fake([
            'https://oauth2.googleapis.com/revoke' => Http::response([], 200),
        ]);

        // Act
        $response = $this->actingAs($user)->delete('/settings/google-calendar');

        // Assert
        $response->assertRedirect('/user/profile');
        $this->assertSame(0, GoogleCalendarConnection::query()->where('user_id', '=', $user->getKey())->count());
        Http::assertSentCount(1);
    }

    public function test_disconnect_without_connection_does_nothing(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->delete('/settings/google-calendar');

        // Assert
        $response->assertRedirect('/user/profile');
    }
}
