<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->withPersonalOrganization()->create();

        $response = $this->actingAs($user)->get('/user/confirm-password');

        $response->assertStatus(200);
    }

    public function test_password_can_be_confirmed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/user/confirm-password', [
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/user/confirm-password', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_password_can_be_confirmed_if_a_placeholder_user_with_the_same_email_exists(): void
    {
        // Arrange
        // Placeholders created by an import have no password at all. The placeholder is created
        // first so that it would be returned by an unordered lookup by email.
        $email = 'shared@example.com';
        User::factory()->placeholder()->create(['email' => $email, 'password' => null]);
        $user = User::factory()->create(['email' => $email, 'password' => Hash::make('secret-password')]);

        // Act
        $response = $this->actingAs($user)->post('/user/confirm-password', [
            'password' => 'secret-password',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertTrue($this->app['session']->has('auth.password_confirmed_at'));
    }

    public function test_password_confirmation_ignores_the_password_of_a_placeholder_user_with_the_same_email(): void
    {
        // Arrange
        // Placeholders created by removing a member copy the password hash as of the removal,
        // so the placeholder holds a password that the real user has since replaced.
        $email = 'shared@example.com';
        User::factory()->placeholder()->create(['email' => $email, 'password' => Hash::make('outdated-password')]);
        $user = User::factory()->create(['email' => $email, 'password' => Hash::make('current-password')]);

        // Act
        $response = $this->actingAs($user)->post('/user/confirm-password', [
            'password' => 'outdated-password',
        ]);

        // Assert
        $response->assertSessionHasErrors();
        $this->assertFalse($this->app['session']->has('auth.password_confirmed_at'));
    }
}
