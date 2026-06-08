<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Web;

use App\Http\Controllers\Web\UserController;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserController::class)]
class UserEndpointTest extends EndpointTestAbstract
{
    public function test_pending_email_verification_updates_email_and_redirects_with_success_banner(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-02 12:00:00', 'UTC'));
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'new@example.com',
            'email_verified_at' => null,
        ]);
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes(60),
            [
                'user' => $user->getKey(),
                'email' => 'NEW@EXAMPLE.COM',
            ],
            false
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('bannerStyle', 'success');
        $response->assertSessionHas('bannerText', 'Your email address has been updated successfully.');
        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertTrue(now()->equalTo($user->email_verified_at));
    }

    public function test_pending_email_verification_is_rejected_for_another_authenticated_user(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'new@example.com',
        ]);
        $this->actingAs(User::factory()->create());
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes(60),
            [
                'user' => $user->getKey(),
                'email' => 'new@example.com',
            ],
            false
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $response->assertForbidden();
        $user->refresh();
        $this->assertSame('current@example.com', $user->email);
        $this->assertSame('new@example.com', $user->pending_email);
    }

    public function test_pending_email_verification_without_email_is_rejected(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'new@example.com',
        ]);
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes(60),
            ['user' => $user->getKey()],
            false
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $response->assertForbidden();
        $user->refresh();
        $this->assertSame('current@example.com', $user->email);
        $this->assertSame('new@example.com', $user->pending_email);
    }

    public function test_pending_email_verification_with_non_string_email_is_rejected(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'new@example.com',
        ]);
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes(60),
            [
                'user' => $user->getKey(),
                'email' => ['new@example.com'],
            ],
            false
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $response->assertForbidden();
        $user->refresh();
        $this->assertSame('current@example.com', $user->email);
        $this->assertSame('new@example.com', $user->pending_email);
    }

    public function test_stale_pending_email_verification_link_is_rejected(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'newer@example.com',
        ]);
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes(60),
            [
                'user' => $user->getKey(),
                'email' => 'older@example.com',
            ],
            false
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $response->assertForbidden();
        $user->refresh();
        $this->assertSame('current@example.com', $user->email);
        $this->assertSame('newer@example.com', $user->pending_email);
    }

    public function test_pending_email_verification_redirects_with_danger_banner_when_email_already_in_use(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'taken@example.com',
            'is_placeholder' => false,
        ]);
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'taken@example.com',
        ]);
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes(60),
            [
                'user' => $user->getKey(),
                'email' => 'taken@example.com',
            ],
            false
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('bannerStyle', 'danger');
        $response->assertSessionHas('bannerText', 'The email address is already in use.');
        $user->refresh();
        $this->assertSame('current@example.com', $user->email);
        $this->assertSame('taken@example.com', $user->pending_email);
    }

    public function test_pending_email_verification_ignores_placeholder_users_with_the_same_email(): void
    {
        // Arrange
        User::factory()->placeholder()->create([
            'email' => 'new@example.com',
        ]);
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'new@example.com',
            'email_verified_at' => null,
        ]);
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes(60),
            [
                'user' => $user->getKey(),
                'email' => 'new@example.com',
            ],
            false
        );

        // Act
        $response = $this->get($verificationUrl);

        // Assert
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('bannerStyle', 'success');
        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_pending_email_verification_with_invalid_signature_is_rejected(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'new@example.com',
        ]);
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'users.verify-email-change',
            now()->addMinutes(60),
            [
                'user' => $user->getKey(),
                'email' => 'new@example.com',
            ],
            false
        );

        // Act
        $response = $this->get($verificationUrl.'&invalid');

        // Assert
        $response->assertForbidden();
        $user->refresh();
        $this->assertSame('current@example.com', $user->email);
        $this->assertSame('new@example.com', $user->pending_email);
    }
}
