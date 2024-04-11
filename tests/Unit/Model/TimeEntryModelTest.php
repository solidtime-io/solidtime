<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;

class TimeEntryModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $timeEntry = TimeEntry::factory()->forUser($user)->create();

        // Act
        $timeEntry->refresh();
        $userRel = $timeEntry->user;

        // Assert
        $this->assertNotNull($userRel);
        $this->assertTrue($userRel->is($user));
    }

    public function test_it_belongs_to_a_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timeEntry = TimeEntry::factory()->forOrganization($organization)->create();

        // Act
        $timeEntry->refresh();
        $organizationRel = $timeEntry->organization;

        // Assert
        $this->assertNotNull($organizationRel);
        $this->assertTrue($organizationRel->is($organization));
    }

    public function test_it_can_belong_to_a_project(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $timeEntry = TimeEntry::factory()->forProject($project)->create();

        // Act
        $timeEntry->refresh();
        $projectRel = $timeEntry->project;

        // Assert
        $this->assertNotNull($projectRel);
        $this->assertTrue($projectRel->is($project));
    }

    public function test_it_can_belong_to_no_project(): void
    {
        // Arrange
        $timeEntry = TimeEntry::factory()->forProject(null)->create();

        // Act
        $timeEntry->refresh();
        $project = $timeEntry->project;

        // Assert
        $this->assertNull($project);
    }

    public function test_it_can_belong_to_a_task(): void
    {
        // Arrange
        $task = Task::factory()->create();
        $timeEntry = TimeEntry::factory()->forTask($task)->create();

        // Act
        $timeEntry->refresh();
        $taskRel = $timeEntry->task;

        // Assert
        $this->assertNotNull($taskRel);
        $this->assertTrue($taskRel->is($task));
    }

    public function test_it_can_belong_to_no_task(): void
    {
        // Arrange
        $timeEntry = TimeEntry::factory()->forTask(null)->create();

        // Act
        $timeEntry->refresh();
        $taskRel = $timeEntry->task;

        // Assert
        $this->assertNull($taskRel);
    }

    public function test_eloquent_datetime_columns_remove_timezone_information_during_save(): void
    {
        // Arrange
        $timeEntry = TimeEntry::factory()->forTask(null)->create();

        // Act
        $timeEntry->start = Carbon::create(2021, 1, 1, 12, 0, 0, 'UTC')->timezone('+1');
        $timeEntry->save();

        // Assert
        $timeEntry->refresh();
        $this->assertSame('UTC', $timeEntry->start->getTimezone()->toRegionName());
        $this->assertSame('2021-01-01 13:00:00', $timeEntry->start->toDateTimeString());
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->id,
            'start' => '2021-01-01 13:00:00',
        ]);
    }
}
