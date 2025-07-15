<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Organization;
use App\Models\Tag;
use App\Models\TimeEntry;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Tag::class)]
class TagModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $tag = Tag::factory()->forOrganization($organization)->create();

        // Act
        $tag->refresh();
        $organizationRel = $tag->organization;

        // Assert
        $this->assertNotNull($organizationRel);
        $this->assertTrue($organizationRel->is($organization));
    }

    public function test_it_has_many_time_entries_via_json_field(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $tag1 = Tag::factory()->forOrganization($organization)->create();
        $tag2 = Tag::factory()->forOrganization($organization)->create();
        $timeEntry1 = TimeEntry::factory()->forOrganization($organization)->create([
            'tags' => [$tag1->id, $tag2->id],
        ]);
        $timeEntry2 = TimeEntry::factory()->forOrganization($organization)->create([
            'tags' => [$tag1->id],
        ]);
        $timeEntry3 = TimeEntry::factory()->forOrganization($organization)->create([
            'tags' => [$tag2->id],
        ]);

        // Act
        $tag1->refresh();
        $timeEntries = $tag1->timeEntries;

        // Assert
        $this->assertCount(2, $timeEntries);
        $this->assertEqualsCanonicalizing([$timeEntry1->getKey(), $timeEntry2->getKey()], $timeEntries->pluck('id')->toArray());
    }
}
