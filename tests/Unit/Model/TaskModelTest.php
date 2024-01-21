<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;

class TaskModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_organization(): void
    {
        // Arrange
        $organization = Team::factory()->create();
        $task = Task::factory()->forOrganization($organization)->create();

        // Act
        $task->refresh();
        $organizationRel = $task->organization;

        // Assert
        $this->assertNotNull($organizationRel);
        $this->assertTrue($organizationRel->is($organization));
    }

    public function test_it_belongs_to_a_project(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $task = Task::factory()->forProject($project)->create();

        // Act
        $task->refresh();
        $projectRel = $task->project;

        // Assert
        $this->assertNotNull($projectRel);
        $this->assertTrue($projectRel->is($project));
    }
}
