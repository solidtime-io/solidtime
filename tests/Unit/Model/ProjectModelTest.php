<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;

class ProjectModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();

        // Act
        $project->refresh();
        $organizationRel = $project->organization;

        // Assert
        $this->assertNotNull($organizationRel);
        $this->assertTrue($organizationRel->is($organization));
    }

    public function test_it_can_belong_to_a_client(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $project = Project::factory()->forClient($client)->create();

        // Act
        $project->refresh();
        $clientRel = $project->client;

        // Assert
        $this->assertNotNull($clientRel);
        $this->assertTrue($clientRel->is($client));
    }

    public function test_it_can_belong_to_no_client(): void
    {
        // Arrange
        $project = Project::factory()->forClient(null)->create();

        // Act
        $project->refresh();
        $clientRel = $project->client;

        // Assert
        $this->assertNull($clientRel);
    }

    public function test_it_has_many_tasks(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $tasks = Task::factory()->forProject($project)->createMany(3);

        // Act
        $project->refresh();
        $tasksRel = $project->tasks;

        // Assert
        $this->assertNotNull($tasksRel);
        $this->assertCount(3, $tasksRel);
        $this->assertTrue($tasksRel->first()->is($tasks->first()));
    }

    public function test_it_has_many_members(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $members = ProjectMember::factory()->forProject($project)->createMany(3);

        // Act
        $project->refresh();
        $membersRel = $project->members;

        // Assert
        $this->assertNotNull($membersRel);
        $this->assertCount(3, $membersRel);
        $this->assertTrue($membersRel->first()->is($members->first()));
    }
}
