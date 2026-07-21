<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Http\Controllers\Web\GoogleAuthController;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GoogleAuthController::class)]
class GoogleAuthEndpointTest extends EndpointTestAbstract
{
    private function enableGoogleSso(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'https://solidtime.test/auth/google/callback',
            'services.google.sso_enabled' => true,
        ]);
    }

    private function mockSocialiteCallback(string $googleId, string $email, string $name): void
    {
        $googleUser = new SocialiteUser;
        $googleUser->map([
            'id' => $googleId,
            'email' => $email,
            'name' => $name,
        ]);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_redirect_returns_not_found_if_sso_is_disabled(): void
    {
        // Act
        $response = $this->get('/auth/google/redirect');

        // Assert
        $response->assertNotFound();
    }

    public function test_redirect_redirects_to_google_if_sso_is_enabled(): void
    {
        // Arrange
        $this->enableGoogleSso();

        // Act
        $response = $this->get('/auth/google/redirect');

        // Assert
        $response->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth', $response->headers->get('Location'));
    }

    public function test_callback_returns_not_found_if_sso_is_disabled(): void
    {
        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertNotFound();
    }

    public function test_callback_logs_in_existing_user_with_linked_google_account(): void
    {
        // Arrange
        $this->enableGoogleSso();
        $user = User::factory()->create([
            'google_id' => 'google-id-1',
        ]);
        $this->mockSocialiteCallback('google-id-1', $user->email, $user->name);

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_links_google_account_to_existing_user_with_same_email(): void
    {
        // Arrange
        $this->enableGoogleSso();
        $user = User::factory()->create();
        $this->mockSocialiteCallback('google-id-2', $user->email, $user->name);

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertSame('google-id-2', $user->google_id);
    }

    public function test_callback_provisions_new_user_with_verified_email_and_no_password(): void
    {
        // Arrange
        $this->enableGoogleSso();
        $this->mockSocialiteCallback('google-id-3', 'new.user@example.com', 'New User');

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertRedirect('/dashboard');
        /** @var User|null $user */
        $user = User::query()->where('email', '=', 'new.user@example.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-id-3', $user->google_id);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(1, $user->organizations()->count());
    }

    public function test_callback_does_not_provision_new_user_if_auto_provision_is_disabled(): void
    {
        // Arrange
        $this->enableGoogleSso();
        config(['services.google.sso_auto_provision' => false]);
        $this->mockSocialiteCallback('google-id-4', 'new.user@example.com', 'New User');

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->assertSame(0, User::query()->where('email', '=', 'new.user@example.com')->count());
    }

    public function test_callback_rejects_email_from_domain_that_is_not_allowed(): void
    {
        // Arrange
        $this->enableGoogleSso();
        config(['services.google.sso_allowed_domains' => 'example.com, example.org']);
        $this->mockSocialiteCallback('google-id-5', 'someone@other-domain.com', 'Someone Else');

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->assertSame(0, User::query()->where('email', '=', 'someone@other-domain.com')->count());
    }

    public function test_callback_allows_email_from_allowed_domain(): void
    {
        // Arrange
        $this->enableGoogleSso();
        config(['services.google.sso_allowed_domains' => 'example.com']);
        $this->mockSocialiteCallback('google-id-6', 'someone@example.com', 'Someone');

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_callback_rejects_google_account_if_email_is_linked_to_a_different_google_account(): void
    {
        // Arrange
        $this->enableGoogleSso();
        $user = User::factory()->create([
            'google_id' => 'google-id-7',
        ]);
        $this->mockSocialiteCallback('google-id-other', $user->email, $user->name);

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertRedirect('/login');
        $this->assertGuest();
        $user->refresh();
        $this->assertSame('google-id-7', $user->google_id);
    }

    public function test_users_without_password_can_not_login_via_password_login(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => null,
        ]);

        // Act
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Assert
        $this->assertGuest();
    }
}
