<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Enums\GoalType;
use App\Models\Goal;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Goal::class)]
class GoalModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_an_organization_and_a_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $goal = Goal::factory()->forMember($member)->create();

        // Act
        $goal->refresh();

        // Assert
        $this->assertTrue($goal->organization->is($organization));
        $this->assertTrue($goal->member->is($member));
        $this->assertTrue($goal->isPersonalGoalOf($member));
        $this->assertTrue($member->goals->contains($goal));
    }

    public function test_factory_default_creates_the_member_in_the_organization_of_the_goal(): void
    {
        // Act
        $goal = Goal::factory()->create();

        // Assert
        $this->assertNotNull($goal->member);
        $this->assertSame($goal->organization_id, $goal->member->organization_id);
    }

    public function test_goal_for_every_member_has_no_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $goal = Goal::factory()->forOrganization($organization)->forEveryMember()->create();

        // Act
        $goal->refresh();

        // Assert
        $this->assertNull($goal->member);
        $this->assertNull($goal->member_id);
        $this->assertSame(GoalType::Organization, $goal->type);
    }

    public function test_an_organization_goal_for_a_member_is_not_a_personal_goal_of_that_member(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        $goal = Goal::factory()->organizationGoalForMember($member)->create();

        // Act
        $goal->refresh();

        // Assert
        $this->assertSame($member->getKey(), $goal->member_id);
        $this->assertFalse($goal->isPersonalGoalOf($member));
    }

    public function test_archived_at_decides_the_is_archived_attribute_and_the_scopes(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $goal = Goal::factory()->forOrganization($organization)->create();
        $archivedGoal = Goal::factory()->forOrganization($organization)->archived()->create();

        // Act
        $notArchivedIds = Goal::query()->notArchived()->pluck('id')->all();
        $archivedIds = Goal::query()->archived()->pluck('id')->all();

        // Assert
        $this->assertFalse($goal->refresh()->is_archived);
        $this->assertTrue($archivedGoal->refresh()->is_archived);
        $this->assertNotNull($archivedGoal->archived_at);
        $this->assertSame([$goal->getKey()], $notArchivedIds);
        $this->assertSame([$archivedGoal->getKey()], $archivedIds);
    }

    public function test_a_member_that_still_has_a_goal_can_not_be_deleted(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();
        Goal::factory()->forMember($member)->create();

        // Assert
        $this->expectException(QueryException::class);

        // Act
        $member->delete();
    }
}
