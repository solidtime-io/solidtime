<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\SelfHost;

use App\Console\Commands\SelfHost\SelfHostDatabaseConsistency;
use App\Enums\Role;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(SelfHostDatabaseConsistency::class)]
#[UsesClass(SelfHostDatabaseConsistency::class)]
class SelfHostDatabaseConsistencyCommandTest extends TestCaseWithDatabase
{
    public function test_checks_that_task_need_to_be_part_of_project_in_time_entries(): void
    {
        // Arrange
        $user = $this->createUserWithRole(Role::Owner);
        $project1 = Project::factory()->forOrganization($user->organization)->create();
        $project2 = Project::factory()->forOrganization($user->organization)->create();
        $task = Task::factory()->forOrganization($user->organization)->forProject($project1)->create();
        $timeEntry = TimeEntry::factory()->forMember($user->member)->forTask($task)->forProject($project2)->create();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:database-consistency');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Consistency problem: Time entries have a task that does not belong to the project of the time entry\n  - ".$timeEntry->getKey()."\n", $output);
    }

    public function test_checks_that_client_id_is_the_client_id_of_the_project(): void
    {
        // Arrange
        $user = $this->createUserWithRole(Role::Owner);
        $client1 = Client::factory()->forOrganization($user->organization)->create();
        $client2 = Client::factory()->forOrganization($user->organization)->create();
        $project = Project::factory()->forOrganization($user->organization)->forClient($client1)->create();
        $timeEntry = TimeEntry::factory()->forMember($user->member)->forProject($project)->create([
            'client_id' => $client2->id,
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:database-consistency');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Consistency problem: Time entries have a client that does not match the client of the project\n  - ".$timeEntry->getKey()."\n", $output);
    }

    public function test_checks_that_client_id_is_the_client_id_of_the_project_with_no_client_in_time_entry(): void
    {
        // Arrange
        $user = $this->createUserWithRole(Role::Owner);
        $client1 = Client::factory()->forOrganization($user->organization)->create();
        $client2 = Client::factory()->forOrganization($user->organization)->create();
        $project = Project::factory()->forOrganization($user->organization)->forClient($client1)->create();
        $timeEntry = TimeEntry::factory()->forMember($user->member)->forProject($project)->create([
            'client_id' => null,
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:database-consistency');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Consistency problem: Time entries have a client that does not match the client of the project\n  - ".$timeEntry->getKey()."\n", $output);
    }

    public function test_checks_that_client_id_is_only_null_if_project_is_also_null(): void
    {
        // Arrange
        $user = $this->createUserWithRole(Role::Owner);
        $client1 = Client::factory()->forOrganization($user->organization)->create();
        $project = Project::factory()->forOrganization($user->organization)->forClient($client1)->create();
        $timeEntry = TimeEntry::factory()->forMember($user->member)->create([
            'client_id' => $client1->getKey(),
        ]);

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:database-consistency');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Consistency problem: Time entries have a client but no project\n  - ".$timeEntry->getKey()."\n", $output);
    }

    public function test_checks_that_every_user_needs_to_be_a_member_of_at_least_one_organization(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:database-consistency');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Consistency problem: Users are not member of any organization\n  - ".$user->getKey()."\n", $output);
    }

    public function test_checks_that_every_organization_needs_at_least_an_owner(): void
    {
        // Arrange
        $user = $this->createUserWithRole(Role::Owner);
        $organization = Organization::factory()->withOwner($user->user)->create();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:database-consistency');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Consistency problem: Organizations without an owner\n  - ".$organization->getKey()."\n", $output);
    }

    public function test_checks_that_every_member_can_only_have_one_running_time_entry(): void
    {
        // Arrange
        $user = $this->createUserWithRole(Role::Owner);
        $timeEntry1 = TimeEntry::factory()->forMember($user->member)->active()->create();
        $timeEntry2 = TimeEntry::factory()->forMember($user->member)->active()->create();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:database-consistency');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Consistency problem: Users with more than one running time entry\n  - ".$user->user->getKey()."\n", $output);
    }

    public function test_checks_that_users_have_a_current_organization_that_they_are_not_a_member_of(): void
    {
        // Arrange
        $user1 = $this->createUserWithRole(Role::Owner);
        $user2 = $this->createUserWithRole(Role::Owner);
        $user1->user->currentOrganization()->associate($user2->organization);
        $user1->user->save();

        // Act
        $exitCode = $this->withoutMockingConsoleOutput()->artisan('self-host:database-consistency');

        // Assert
        $this->assertSame(Command::FAILURE, $exitCode);
        $output = Artisan::output();
        $this->assertSame("Consistency problem: Users have a current organization that they are not a member of\n  - ".$user1->user->getKey()."\n", $output);
    }
}
