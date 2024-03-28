<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

class ProjectMemberModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_project(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $projectMember = ProjectMember::factory()->forProject($project)->forUser($user)->create();

        // Act
        $projectMember->refresh();
        $projectRel = $projectMember->project;

        // Assert
        $this->assertNotNull($projectRel);
        $this->assertTrue($projectRel->is($project));
    }

    public function test_it_belongs_to_a_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $projectMember = ProjectMember::factory()->forUser($user)->create();

        // Act
        $projectMember->refresh();
        $userRel = $projectMember->user;

        // Assert
        $this->assertNotNull($userRel);
        $this->assertTrue($userRel->is($user));
    }
}
