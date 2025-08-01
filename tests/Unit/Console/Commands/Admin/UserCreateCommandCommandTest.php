<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Admin;

use App\Console\Commands\Admin\UserCreateCommand;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(UserCreateCommand::class)]
class UserCreateCommandCommandTest extends TestCaseWithDatabase
{
    public function test_it_creates_user(): void
    {
        // Arrange
        $email = 'mail@testuser.test';
        $name = 'Test User';

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('admin:user:create', [
            'name' => $name,
            'email' => $email,
        ]);

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Created user "'.$name.'" ("'.$email.'")', $output);
        $this->assertDatabaseHas(User::class, [
            'name' => $name,
            'email' => $email,
            'email_verified_at' => null,
        ]);
    }

    public function test_created_user_is_verified_if_option_is_set(): void
    {
        // Arrange
        $email = 'mail@testuser.test';
        $name = 'Test User';

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('admin:user:create', [
            'name' => $name,
            'email' => $email,
            '--verify-email' => true,
        ]);

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Created user "'.$name.'" ("'.$email.'")', $output);
        $this->assertDatabaseHas(User::class, [
            'name' => $name,
            'email' => $email,
        ]);
        $user = User::where('email', $email)->first();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_it_fails_if_user_with_email_already_exists(): void
    {
        // Arrange
        $email = 'mail@testuser.test';
        $name = 'Test User';

        User::factory()->create([
            'email' => $email,
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('admin:user:create', [
            'name' => $name,
            'email' => $email,
        ]);

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('User with email "'.$email.'" already exists.', $output);
    }

    public function test_it_asks_for_password_if_option_is_set(): void
    {
        // Arrange
        $email = 'mail@testuser.test';
        $name = 'Test User';

        // Act
        $this->artisan('admin:user:create', [
            'name' => $name,
            'email' => $email,
            '--ask-for-password' => true,
        ])
            ->expectsQuestion('Enter the password', 'password')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas(User::class, [
            'name' => $name,
            'email' => $email,
            'email_verified_at' => null,
        ]);
        $user = User::where('email', $email)->first();
        $this->assertNotNull($user->password);
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
