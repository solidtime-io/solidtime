<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Service\AuthenticationContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_an_unknown_email(): void
    {
        $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_users_without_a_local_password_can_not_authenticate(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['password' => null])->save();

        $this->post('/login', [
            'email' => $user->email,
            'password' => '',
        ]);

        $this->assertGuest();
    }

    public function test_users_without_a_local_password_can_not_authenticate_with_a_null_password(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['password' => null])->save();

        $this->post('/login', [
            'email' => $user->email,
            'password' => null,
        ]);

        $this->assertGuest();
    }

    public function test_users_without_a_local_password_can_not_authenticate_without_a_password_field(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['password' => null])->save();

        $this->post('/login', [
            'email' => $user->email,
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_a_valid_password_if_password_login_is_not_allowed(): void
    {
        $user = User::factory()->create();
        $this->partialMock(AuthenticationContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isPasswordLoginAllowed')->andReturn(false);
        });

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
