<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Admin;

use App\Console\Commands\Admin\UserCreateCommand;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(UserCreateCommand::class)]
class UserCreateCommandCommandTest extends TestCaseWithDatabase
{
    public function test_it_creates_user(): void
    {
        // Arrange
        Event::fake([Verified::class]);
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
        Event::assertNotDispatched(Verified::class);
    }

    public function test_created_user_is_verified_if_option_is_set(): void
    {
        // Arrange
        Event::fake([Verified::class]);
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
        Event::assertDispatched(fn (Verified $event): bool => $event->user->is($user));
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

    public function test_it_stores_email_lowercased(): void
    {
        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('admin:user:create', [
            'name' => 'Test User',
            'email' => 'Mail@TestUser.test',
        ]);

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertDatabaseHas(User::class, [
            'email' => 'mail@testuser.test',
        ]);
    }

    public function test_it_fails_if_user_with_email_in_different_case_already_exists(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'mail@testuser.test',
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('admin:user:create', [
            'name' => 'Test User',
            'email' => 'MAIL@testuser.test',
        ]);

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('User with email "mail@testuser.test" already exists.', $output);
        $this->assertSame(1, User::query()->count());
    }

    public function test_it_fails_if_email_is_invalid(): void
    {
        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('admin:user:create', [
            'name' => 'Test User',
            'email' => 'not-an-email',
        ]);

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('"not-an-email" is not a valid email address.', $output);
        $this->assertSame(0, User::query()->count());
    }
}
