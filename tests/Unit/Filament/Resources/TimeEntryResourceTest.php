<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Resources;

use App\Filament\Resources\TimeEntryResource;
use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(TimeEntryResource::class)]
class TimeEntryResourceTest extends FilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('auth.super_admins', ['admin@example.com']);
        $user = User::factory()->withPersonalOrganization()->create([
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($user);
    }

    public function test_can_list_time_entry(): void
    {
        // Arrange
        $timeEntry = TimeEntry::factory()->createMany(5);

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\ListTimeEntries::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($timeEntry);
    }

    public function test_can_see_edit_page_of_time_entry(): void
    {
        // Arrange
        $timeEntry = TimeEntry::factory()->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\EditTimeEntry::class, ['record' => $timeEntry->getKey()]);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_see_create_page_of_time_entry(): void
    {
        // Act
        $response = Livewire::test(TimeEntryResource\Pages\CreateTimeEntry::class);

        // Assert
        $response->assertSuccessful();
    }

    public function test_can_create_time_entry(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()
            ->forOrganization($organization)
            ->forUser($user)
            ->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\CreateTimeEntry::class)
            ->fillForm([
                'description' => 'Test time entry',
                'billable' => true,
                'start' => '2024-01-01 08:00:00',
                'end' => '2024-01-01 10:00:00',
                'member_id' => $member->getKey(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Assert
        $response->assertSuccessful();
        $timeEntry = TimeEntry::where('description', 'Test time entry')->first();
        $this->assertNotNull($timeEntry);
        $this->assertSame($member->getKey(), $timeEntry->member_id);
        $this->assertSame($user->getKey(), $timeEntry->user_id);
        $this->assertSame($organization->getKey(), $timeEntry->organization_id);
        $this->assertTrue($timeEntry->billable);
    }

    public function test_can_create_time_entry_and_derives_user_and_organization_from_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()
            ->forOrganization($organization)
            ->forUser($user)
            ->create();
        $otherUser = User::factory()->create();
        $otherOrganization = Organization::factory()->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\CreateTimeEntry::class)
            ->fillForm([
                'description' => 'Derived fields test',
                'billable' => false,
                'start' => '2024-03-01 09:00:00',
                'end' => '2024-03-01 11:00:00',
                'member_id' => $member->getKey(),
                'user_id' => $otherUser->getKey(),
                'organization_id' => $otherOrganization->getKey(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Assert
        $response->assertSuccessful();
        $timeEntry = TimeEntry::where('description', 'Derived fields test')->first();
        $this->assertNotNull($timeEntry);
        $this->assertSame($user->getKey(), $timeEntry->user_id);
        $this->assertSame($organization->getKey(), $timeEntry->organization_id);
    }

    public function test_can_update_time_entry(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()
            ->forOrganization($organization)
            ->forUser($user)
            ->create();
        $timeEntry = TimeEntry::factory()->forMember($member)->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\EditTimeEntry::class, ['record' => $timeEntry->getKey()])
            ->fillForm([
                'description' => 'Updated description',
                'billable' => true,
                'start' => '2024-02-01 08:00:00',
                'end' => '2024-02-01 12:00:00',
                'member_id' => $member->getKey(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert
        $response->assertSuccessful();
        $timeEntry->refresh();
        $this->assertSame('Updated description', $timeEntry->description);
        $this->assertTrue($timeEntry->billable);
        $this->assertSame($user->getKey(), $timeEntry->user_id);
        $this->assertSame($organization->getKey(), $timeEntry->organization_id);
    }

    public function test_update_time_entry_derives_user_and_organization_from_new_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $member = Member::factory()
            ->forOrganization($organization)
            ->forUser($user)
            ->create();
        $timeEntry = TimeEntry::factory()->create();

        $newOrganization = Organization::factory()->create();
        $newUser = User::factory()->create();
        $newMember = Member::factory()
            ->forOrganization($newOrganization)
            ->forUser($newUser)
            ->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\EditTimeEntry::class, ['record' => $timeEntry->getKey()])
            ->fillForm([
                'description' => 'Reassigned entry',
                'billable' => false,
                'start' => '2024-02-01 08:00:00',
                'end' => '2024-02-01 12:00:00',
                'member_id' => $newMember->getKey(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert
        $response->assertSuccessful();
        $timeEntry->refresh();
        $this->assertSame($newMember->getKey(), $timeEntry->member_id);
        $this->assertSame($newUser->getKey(), $timeEntry->user_id);
        $this->assertSame($newOrganization->getKey(), $timeEntry->organization_id);
    }

    public function test_update_time_entry_sets_client_of_project(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $oldProject = Project::factory()->forOrganization($organization)->forClient(Client::factory()->forOrganization($organization)->create())->create();
        $newClient = Client::factory()->forOrganization($organization)->create();
        $newProject = Project::factory()->forOrganization($organization)->forClient($newClient)->create();
        $timeEntry = TimeEntry::factory()->forMember($member)->forProject($oldProject)->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\EditTimeEntry::class, ['record' => $timeEntry->getKey()])
            ->fillForm([
                'member_id' => $member->getKey(),
                'project_id' => $newProject->getKey(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert
        $response->assertSuccessful();
        $timeEntry->refresh();
        $this->assertSame($newProject->getKey(), $timeEntry->project_id);
        $this->assertSame($newClient->getKey(), $timeEntry->client_id);
    }

    public function test_update_time_entry_removes_client_if_project_is_removed(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $project = Project::factory()->forOrganization($organization)->forClient(Client::factory()->forOrganization($organization)->create())->create();
        $timeEntry = TimeEntry::factory()->forMember($member)->forProject($project)->create();

        // Act
        $response = Livewire::test(TimeEntryResource\Pages\EditTimeEntry::class, ['record' => $timeEntry->getKey()])
            ->fillForm([
                'member_id' => $member->getKey(),
                'project_id' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert
        $response->assertSuccessful();
        $timeEntry->refresh();
        $this->assertNull($timeEntry->project_id);
        $this->assertNull($timeEntry->client_id);
    }

    /**
     * @return array{0: Member, 1: Project, 2: Task}
     */
    private function createMemberWithProjectAndTask(): array
    {
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $project = Project::factory()->forOrganization($organization)->create(['spent_time' => 0]);
        $task = Task::factory()->forProject($project)->forOrganization($organization)->create(['spent_time' => 0]);

        return [$member, $project, $task];
    }

    private function createTimeEntry(Member $member, Project $project, Task $task, int $durationInSeconds): TimeEntry
    {
        $timeEntry = TimeEntry::factory()
            ->forMember($member)
            ->forProject($project)
            ->forTask($task)
            ->startWithDuration(Carbon::now()->subDay(), $durationInSeconds)
            ->create();
        $project->increment('spent_time', $durationInSeconds);
        $task->increment('spent_time', $durationInSeconds);

        return $timeEntry;
    }

    public function test_creating_time_entry_recalculates_spent_time_of_project_and_task(): void
    {
        // Arrange
        [$member, $project, $task] = $this->createMemberWithProjectAndTask();

        // Act
        Livewire::test(TimeEntryResource\Pages\CreateTimeEntry::class)
            ->fillForm([
                'description' => 'Test time entry',
                'billable' => true,
                'start' => '2024-01-01 08:00:00',
                'end' => '2024-01-01 10:00:00',
                'member_id' => $member->getKey(),
                'project_id' => $project->getKey(),
                'task_id' => $task->getKey(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Assert
        $this->assertSame(7200, $project->refresh()->spent_time);
        $this->assertSame(7200, $task->refresh()->spent_time);
    }

    public function test_updating_time_entry_recalculates_spent_time_of_old_and_new_project_and_task(): void
    {
        // Arrange
        [$member, $oldProject, $oldTask] = $this->createMemberWithProjectAndTask();
        $newProject = Project::factory()->forOrganization($member->organization)->create(['spent_time' => 0]);
        $newTask = Task::factory()->forProject($newProject)->forOrganization($member->organization)->create(['spent_time' => 0]);
        $timeEntry = $this->createTimeEntry($member, $oldProject, $oldTask, 3600);
        $this->assertSame(3600, $oldProject->refresh()->spent_time);

        // Act
        Livewire::test(TimeEntryResource\Pages\EditTimeEntry::class, ['record' => $timeEntry->getKey()])
            ->fillForm([
                'start' => '2024-02-01 08:00:00',
                'end' => '2024-02-01 12:00:00',
                'project_id' => $newProject->getKey(),
                'task_id' => $newTask->getKey(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert
        $this->assertSame(0, $oldProject->refresh()->spent_time);
        $this->assertSame(0, $oldTask->refresh()->spent_time);
        $this->assertSame(14400, $newProject->refresh()->spent_time);
        $this->assertSame(14400, $newTask->refresh()->spent_time);
    }

    public function test_deleting_time_entry_recalculates_spent_time_of_project_and_task(): void
    {
        // Arrange
        [$member, $project, $task] = $this->createMemberWithProjectAndTask();
        $this->createTimeEntry($member, $project, $task, 600);
        $timeEntry = $this->createTimeEntry($member, $project, $task, 3600);
        $this->assertSame(4200, $project->refresh()->spent_time);

        // Act
        Livewire::test(TimeEntryResource\Pages\EditTimeEntry::class, ['record' => $timeEntry->getKey()])
            ->callAction(DeleteAction::class);

        // Assert
        $this->assertDatabaseMissing(TimeEntry::class, ['id' => $timeEntry->getKey()]);
        $this->assertSame(600, $project->refresh()->spent_time);
        $this->assertSame(600, $task->refresh()->spent_time);
    }

    public function test_bulk_deleting_time_entries_recalculates_spent_time_of_projects_and_tasks(): void
    {
        // Arrange
        [$member1, $project1, $task1] = $this->createMemberWithProjectAndTask();
        [$member2, $project2, $task2] = $this->createMemberWithProjectAndTask();
        $remaining = $this->createTimeEntry($member1, $project1, $task1, 600);
        $toDelete = collect([
            $this->createTimeEntry($member1, $project1, $task1, 3600),
            $this->createTimeEntry($member2, $project2, $task2, 1800),
        ]);

        // Act
        Livewire::test(TimeEntryResource\Pages\ListTimeEntries::class)
            ->callTableBulkAction('delete', $toDelete);

        // Assert
        $this->assertDatabaseHas(TimeEntry::class, ['id' => $remaining->getKey()]);
        $this->assertSame(600, $project1->refresh()->spent_time);
        $this->assertSame(600, $task1->refresh()->spent_time);
        $this->assertSame(0, $project2->refresh()->spent_time);
        $this->assertSame(0, $task2->refresh()->spent_time);
    }
}
