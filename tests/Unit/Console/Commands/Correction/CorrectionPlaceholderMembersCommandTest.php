<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Correction;

use App\Console\Commands\Correction\CorrectionPlaceholderMembersCommand;
use App\Enums\Role;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(CorrectionPlaceholderMembersCommand::class)]
#[UsesClass(CorrectionPlaceholderMembersCommand::class)]
class CorrectionPlaceholderMembersCommandTest extends TestCaseWithDatabase
{
    public function test_sets_member_role_to_placeholder_if_user_is_placeholder(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user1 = User::factory()->placeholder()->create();
        $member1 = Member::factory()->forOrganization($organization)->forUser($user1)->role(Role::Admin)->create();
        $user2 = User::factory()->create();
        $member2 = Member::factory()->forOrganization($organization)->forUser($user2)->role(Role::Admin)->create();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('correction:placeholder-members');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $member1->refresh();
        $this->assertSame(Role::Placeholder->value, $member1->role);
        $member2->refresh();
        $this->assertSame(Role::Admin->value, $member2->role);
        $this->assertSame("Sets all members who belong to a placeholder user to role placeholder...\n".
            'Set role of member (id='.$member1->getKey().") to placeholder\n", $output);
    }

    public function test_sets_member_role_to_placeholder_if_user_is_placeholder_dry_run(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user1 = User::factory()->placeholder()->create();
        $member1 = Member::factory()->forOrganization($organization)->forUser($user1)->role(Role::Admin)->create();
        $user2 = User::factory()->create();
        $member2 = Member::factory()->forOrganization($organization)->forUser($user2)->role(Role::Admin)->create();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('correction:placeholder-members --dry-run');

        // Assert
        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = Artisan::output();
        $member1->refresh();
        $this->assertSame(Role::Admin->value, $member1->role);
        $member2->refresh();
        $this->assertSame(Role::Admin->value, $member2->role);
        $this->assertSame("Sets all members who belong to a placeholder user to role placeholder...\n".
            "Running in dry-run mode. Nothing will be saved to the database.\n".
            'Set role of member (id='.$member1->getKey().") to placeholder\n", $output);
    }
}
