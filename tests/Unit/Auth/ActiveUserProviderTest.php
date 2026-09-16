<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\ActiveUserProvider;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(ActiveUserProvider::class)]
class ActiveUserProviderTest extends TestCaseWithDatabase
{
    public function test_password_broker_uses_the_active_user_provider(): void
    {
        // Act
        $brokerProvider = Auth::createUserProvider(config('auth.passwords.users.provider'));

        // Assert
        $this->assertInstanceOf(ActiveUserProvider::class, $brokerProvider);
    }

    public function test_api_guard_uses_the_active_user_provider(): void
    {
        // Act
        $guardProvider = Auth::createUserProvider(config('auth.guards.api.provider'));

        // Assert
        $this->assertInstanceOf(ActiveUserProvider::class, $guardProvider);
    }

    public function test_web_guard_uses_the_active_user_provider(): void
    {
        // Act
        $guardProvider = Auth::createUserProvider(config('auth.guards.web.provider'));

        // Assert
        $this->assertInstanceOf(ActiveUserProvider::class, $guardProvider);
    }

    public function test_retrieve_by_credentials_ignores_placeholder_users_with_the_same_email(): void
    {
        // Arrange
        $email = 'shared@example.com';
        $placeholder = User::factory()->placeholder()->create(['email' => $email]);
        $user = User::factory()->create(['email' => $email]);
        $provider = Auth::createUserProvider('users');

        // Act
        $result = $provider->retrieveByCredentials(['email' => $email]);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($user->is($result));
        $this->assertFalse($placeholder->is($result));
    }

    public function test_retrieve_by_credentials_returns_null_if_only_a_placeholder_user_exists(): void
    {
        // Arrange
        $email = 'placeholder-only@example.com';
        User::factory()->placeholder()->create(['email' => $email]);
        $provider = Auth::createUserProvider('users');

        // Act
        $result = $provider->retrieveByCredentials(['email' => $email]);

        // Assert
        $this->assertNull($result);
    }

    public function test_retrieve_by_id_returns_null_for_placeholder_users(): void
    {
        // Arrange
        $placeholder = User::factory()->placeholder()->create();
        $user = User::factory()->create();
        $provider = Auth::createUserProvider('users');

        // Act
        $placeholderResult = $provider->retrieveById($placeholder->getKey());
        $userResult = $provider->retrieveById($user->getKey());

        // Assert
        $this->assertNull($placeholderResult);
        $this->assertInstanceOf(User::class, $userResult);
        $this->assertTrue($user->is($userResult));
    }
}
