<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\Tag;
use App\Models\TimeEntry;
use App\Service\TimeEntryFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(TimeEntryFilter::class)]
#[UsesClass(TimeEntryFilter::class)]
class TimeEntryFilterTest extends TestCaseWithDatabase
{
    public function test_add_tag_ids_filter_is_or(): void
    {
        // Arrange
        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);
        $timEntryNoTag = TimeEntry::factory()->create();
        $tag1 = Tag::factory()->create();
        $timeEntryWithTag1 = TimeEntry::factory()->create([
            'tags' => [$tag1->getKey()],
        ]);
        $tag2 = Tag::factory()->create();
        $timeEntryWithTag2 = TimeEntry::factory()->create([
            'tags' => [$tag2->getKey()],
        ]);
        $timeEntryWithAllTags = TimeEntry::factory()->create([
            'tags' => [$tag1->getKey(), $tag2->getKey()],
        ]);

        // Act
        $filter->addTagIdsFilter([$tag1->getKey(), $tag2->getKey()]);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(3, $timeEntries);

    }
}
