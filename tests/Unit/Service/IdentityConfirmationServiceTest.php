<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\User;
use App\Service\AuthenticationContract;
use App\Service\IdentityConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(IdentityConfirmationService::class)]
class IdentityConfirmationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_a_password_confirm_with_it(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $confirmsWithPassword = app(IdentityConfirmationService::class)->confirmsWithPassword($user);

        // Assert
        $this->assertTrue($confirmsWithPassword);
    }

    public function test_users_without_a_password_cannot_confirm(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->forceFill(['password' => null])->save();

        // Act
        $confirmsWithPassword = app(IdentityConfirmationService::class)->confirmsWithPassword($user);

        // Assert
        $this->assertFalse($confirmsWithPassword);
    }

    public function test_users_who_may_not_log_in_with_their_password_cannot_confirm_with_it(): void
    {
        // Arrange
        $service = app(IdentityConfirmationService::class);
        $user = User::factory()->create();
        $this->partialMock(AuthenticationContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isPasswordLoginAllowed')->andReturn(false);
        });

        // Act
        $confirmsWithPassword = $service->confirmsWithPassword($user);

        // Assert
        $this->assertFalse($confirmsWithPassword);
    }

    public function test_confirm_password_accepts_only_the_correct_password(): void
    {
        // Arrange
        $service = app(IdentityConfirmationService::class);
        $user = User::factory()->create();

        // Act
        $correct = $service->confirmPassword($user, 'password');
        $wrong = $service->confirmPassword($user, 'wrong-password');
        $missing = $service->confirmPassword($user, null);

        // Assert
        $this->assertTrue($correct);
        $this->assertFalse($wrong);
        $this->assertFalse($missing);
    }

    public function test_confirm_password_rejects_the_correct_password_of_users_who_may_not_log_in_with_it(): void
    {
        // Arrange
        $service = app(IdentityConfirmationService::class);
        $user = User::factory()->create();
        $this->partialMock(AuthenticationContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isPasswordLoginAllowed')->andReturn(false);
        });

        // Act
        $confirmed = $service->confirmPassword($user, 'password');

        // Assert
        $this->assertFalse($confirmed);
    }
}
