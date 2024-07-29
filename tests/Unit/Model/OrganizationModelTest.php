<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Member;
use App\Models\Organization;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(Organization::class)]
#[UsesClass(Organization::class)]
class OrganizationModelTest extends ModelTestAbstract
{
    public function test_it_has_many_members(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $members = Member::factory()->forOrganization($organization)->createMany(3);

        // Act
        $organization->refresh();
        $membersRel = $organization->members;

        // Assert
        $this->assertNotNull($membersRel);
        $this->assertCount(3, $membersRel);
        $this->assertTrue($membersRel->first()->is($members->first()));
    }
}
