<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Admin;

use App\Console\Commands\Admin\UserVerifyCommand;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(UserVerifyCommand::class)]
class UserVerifyCommandTest extends TestCaseWithDatabase
{
    public function test_it_verifies_user_email(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();

        // Act
        $command = $this->artisan('admin:user:verify', ['email' => $user->email]);

        // Assert
        $command->expectsOutput('Start verifying user with email "'.$user->email.'"')
            ->expectsOutput('User with email "'.$user->email.'" has been verified.')
            ->assertExitCode(0);
    }

    public function test_it_fails_if_user_does_not_exist(): void
    {
        // Arrange
        $email = 'test@test.test';
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'email' => 'other@test.test',
            'email_verified_at' => null,
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();

        // Act
        $command = $this->artisan('admin:user:verify', ['email' => $email]);

        // Assert
        $command->expectsOutput('User with email "'.$email.'" not found.')
            ->assertExitCode(1);
    }

    public function test_it_fails_if_user_email_is_already_verified(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $member = Member::factory()->forUser($user)->forOrganization($organization)->create();

        // Act
        $command = $this->artisan('admin:user:verify', ['email' => $user->email]);

        // Assert
        $command->expectsOutput('User with email "'.$user->email.'" already verified.')
            ->assertExitCode(1);
    }
}
