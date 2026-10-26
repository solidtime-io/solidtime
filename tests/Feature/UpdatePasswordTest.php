<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Service\AuthenticationContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
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

    public function test_password_cannot_be_updated_by_users_who_may_not_log_in_with_it(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->partialMock(AuthenticationContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isPasswordLoginAllowed')->andReturn(false);
        });

        // Act
        $response = $this->actingAs($user)->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        // Assert
        $response->assertSessionHasErrorsIn('updatePassword', ['current_password']);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_cannot_be_set_by_users_without_a_password(): void
    {
        // Arrange
        $user = User::factory()->create(['password' => null]);

        // Act
        $response = $this->actingAs($user)->put('/user/password', [
            'current_password' => '',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        // Assert
        $response->assertSessionHasErrorsIn('updatePassword', ['current_password']);
        $this->assertNull($user->fresh()->password);
    }
}
