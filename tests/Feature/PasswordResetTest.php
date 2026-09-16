<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function (object $notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function (object $notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHasNoErrors();

            return true;
        });
    }

    public function test_password_reset_targets_the_real_user_when_a_placeholder_user_with_the_same_email_exists(): void
    {

        Notification::fake();

        // The placeholder is created first so that it would be returned by an unordered lookup by email
        $email = 'shared@example.com';
        $placeholder = User::factory()->placeholder()->create(['email' => $email]);
        $user = User::factory()->create(['email' => $email]);
        $placeholderPasswordBefore = $placeholder->password;

        $response = $this->post('/forgot-password', [
            'email' => $email,
        ]);

        $response->assertSessionHasNoErrors();
        Notification::assertNotSentTo($placeholder, ResetPassword::class);
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($email) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

            $response->assertSessionHasNoErrors();

            return true;
        });

        $placeholder->refresh();
        $user->refresh();
        $this->assertSame($placeholderPasswordBefore, $placeholder->password);
        $this->assertTrue(Hash::check('new-password-123', $user->password));

        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'new-password-123',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_reset_link_is_not_sent_if_only_a_placeholder_user_with_the_email_exists(): void
    {
        Notification::fake();

        $placeholder = User::factory()->placeholder()->create();

        $response = $this->post('/forgot-password', [
            'email' => $placeholder->email,
        ]);

        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }
}
