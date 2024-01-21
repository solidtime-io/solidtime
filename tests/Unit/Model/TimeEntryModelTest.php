<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Models\User;

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
        $organization = Team::factory()->create();
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
}
