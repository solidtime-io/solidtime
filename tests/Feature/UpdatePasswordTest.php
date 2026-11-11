<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Passport\Client;
use App\Models\Passport\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->create());

        $response = $this->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_updating_password_revokes_app_tokens(): void
    {
        $this->actingAs($user = User::factory()->create());
        $desktopClient = Client::factory()->desktopClient()->create();
        $desktopToken = Token::factory()->forUser($user)->forClient($desktopClient)->create([
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($desktopToken->fresh()->revoked);
    }

    public function test_app_tokens_are_not_revoked_if_current_password_is_incorrect(): void
    {
        $this->actingAs($user = User::factory()->create());
        $desktopClient = Client::factory()->desktopClient()->create();
        $desktopToken = Token::factory()->forUser($user)->forClient($desktopClient)->create([
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->put('/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertFalse($desktopToken->fresh()->revoked);
    }

    public function test_current_password_must_be_correct(): void
    {
        $this->actingAs($user = User::factory()->create());

        $response = $this->put('/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasErrors();

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_new_passwords_must_match(): void
    {
        $this->actingAs($user = User::factory()->create());

        $response = $this->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
