<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Tag;
use App\Models\Team;

class TagModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_organization(): void
    {
        // Arrange
        $organization = Team::factory()->create();
        $task = Tag::factory()->forOrganization($organization)->create();

        // Act
        $task->refresh();
        $organizationRel = $task->organization;

        // Assert
        $this->assertNotNull($organizationRel);
        $this->assertTrue($organizationRel->is($organization));
    }
}
