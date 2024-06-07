<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ProjectMember::class)]
#[UsesClass(ProjectMember::class)]
class ProjectMemberModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_project(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $member = Member::factory()->create();
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($member)->create();

        // Act
        $projectMember->refresh();
        $projectRel = $projectMember->project;

        // Assert
        $this->assertNotNull($projectRel);
        $this->assertTrue($projectRel->is($project));
    }

    public function test_it_belongs_to_a_member(): void
    {
        // Arrange
        $member = Member::factory()->create();
        $projectMember = ProjectMember::factory()->forMember($member)->create();

        // Act
        $projectMember->refresh();
        $memberRel = $projectMember->member;

        // Assert
        $this->assertNotNull($memberRel);
        $this->assertTrue($memberRel->is($member));
    }

    public function test_scope_where_belongs_to_organization_filters_project_members_to_only_retrieve_project_members_that_belong_to_a_project_of_the_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();
        $projectNotBelongingToOrganization = Project::factory()->forOrganization($otherOrganization)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->create();
        $projectMemberNotBelongingToOrganization = ProjectMember::factory()->for($projectNotBelongingToOrganization)->create();

        // Act
        $projectMembers = ProjectMember::whereBelongsToOrganization($organization)->get();

        // Assert
        $this->assertCount(1, $projectMembers);
        $this->assertTrue($projectMembers->first()->is($projectMember));
    }
}
