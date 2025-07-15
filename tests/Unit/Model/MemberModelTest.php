<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Member::class)]
class MemberModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $member = Member::factory()->forUser($user)->create();

        // Act
        $member->refresh();
        $userRel = $member->user;

        // Assert
        $this->assertNotNull($userRel);
        $this->assertTrue($userRel->is($user));
    }

    public function test_it_belongs_to_a_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();

        // Act
        $member->refresh();
        $organizationRel = $member->organization;

        // Assert
        $this->assertNotNull($organizationRel);
        $this->assertTrue($organizationRel->is($organization));
    }

    public function test_it_has_many_project_members(): void
    {
        // Arrange
        $member = Member::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $projectMember1 = ProjectMember::factory()->forMember($member)->forProject($project1)->create();
        $projectMember2 = ProjectMember::factory()->forMember($member)->forProject($project2)->createMany();

        // Act
        $member->refresh();
        $projectMembersRel = $member->projectMembers;

        // Assert
        $this->assertNotNull($projectMembersRel);
        $this->assertCount(2, $projectMembersRel);
        $this->assertEqualsCanonicalizing([
            $projectMember1->getKey(),
            $projectMember2->first()->getKey(),
        ], $projectMembersRel->pluck('id')->all());
    }
}
