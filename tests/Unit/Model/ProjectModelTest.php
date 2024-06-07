<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(Project::class)]
#[UsesClass(Project::class)]
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

    public function test_scope_visible_by_user_filters_so_that_only_public_projects_or_projects_where_the_user_is_member_are_shown(): void
    {
        // Arrange
        $member = Member::factory()->create();
        $projectPrivate = Project::factory()->isPrivate()->create();
        $projectPublic = Project::factory()->isPublic()->create();
        $projectPrivateButMember = Project::factory()->isPrivate()->create();
        ProjectMember::factory()->forProject($projectPrivateButMember)->forMember($member)->create();

        // Act
        $projectsVisible = Project::query()->visibleByEmployee($member->user)->get();
        $allProjects = Project::query()->get();

        // Assert
        $this->assertEqualsIdsOfEloquentCollection([
            $projectPublic->getKey(),
            $projectPrivateButMember->getKey(),
        ], $projectsVisible);
        $this->assertEqualsIdsOfEloquentCollection([
            $projectPrivate->getKey(),
            $projectPublic->getKey(),
            $projectPrivateButMember->getKey(),
        ], $allProjects);
    }
}
