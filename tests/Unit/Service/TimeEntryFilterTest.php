<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\TagMatchType;
use App\Models\Client;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Service\TimeEntryFilter;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(TimeEntryFilter::class)]
class TimeEntryFilterTest extends TestCaseWithDatabase
{
    public function test_add_start_is_inclusive_of_boundary(): void
    {
        // Arrange
        $boundary = Carbon::parse('2024-01-01 12:00:00', 'UTC');
        $entryAtBoundary = TimeEntry::factory()->start($boundary)->create();
        $entryAfterBoundary = TimeEntry::factory()->start($boundary->copy()->addSecond())->create();
        $entryBeforeBoundary = TimeEntry::factory()->start($boundary->copy()->subSecond())->create();

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act
        $filter->addStart($boundary);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(2, $timeEntries);
        $this->assertTrue($timeEntries->contains($entryAtBoundary));
        $this->assertTrue($timeEntries->contains($entryAfterBoundary));
        $this->assertFalse($timeEntries->contains($entryBeforeBoundary));
    }

    public function test_add_end_is_exclusive_of_boundary(): void
    {
        // Arrange
        $boundary = Carbon::parse('2024-01-01 12:00:00', 'UTC');
        $entryAtBoundary = TimeEntry::factory()->start($boundary)->create();
        $entryAfterBoundary = TimeEntry::factory()->start($boundary->copy()->addSecond())->create();
        $entryBeforeBoundary = TimeEntry::factory()->start($boundary->copy()->subSecond())->create();

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act
        $filter->addEnd($boundary);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(1, $timeEntries);
        $this->assertTrue($timeEntries->contains($entryBeforeBoundary));
        $this->assertFalse($timeEntries->contains($entryAtBoundary));
        $this->assertFalse($timeEntries->contains($entryAfterBoundary));
    }

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

    public function test_add_project_ids_filter_with_none_returns_entries_without_project(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $timeEntryWithProject = TimeEntry::factory()->create([
            'project_id' => $project->getKey(),
            'organization_id' => $project->organization_id,
        ]);
        $timeEntryWithoutProject = TimeEntry::factory()->create([
            'project_id' => null,
        ]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act
        $filter->addProjectIdsFilter([TimeEntryFilter::NONE_VALUE]);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(1, $timeEntries);
        $this->assertTrue($timeEntries->contains($timeEntryWithoutProject));
        $this->assertFalse($timeEntries->contains($timeEntryWithProject));
    }

    public function test_add_project_ids_filter_with_none_and_ids_returns_both(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $timeEntryWithProject = TimeEntry::factory()->create([
            'project_id' => $project->getKey(),
            'organization_id' => $project->organization_id,
        ]);
        $timeEntryWithoutProject = TimeEntry::factory()->create([
            'project_id' => null,
        ]);
        $otherProject = Project::factory()->create();
        $timeEntryWithOtherProject = TimeEntry::factory()->create([
            'project_id' => $otherProject->getKey(),
            'organization_id' => $otherProject->organization_id,
        ]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act
        $filter->addProjectIdsFilter([$project->getKey(), TimeEntryFilter::NONE_VALUE]);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(2, $timeEntries);
        $this->assertTrue($timeEntries->contains($timeEntryWithProject));
        $this->assertTrue($timeEntries->contains($timeEntryWithoutProject));
        $this->assertFalse($timeEntries->contains($timeEntryWithOtherProject));
    }

    public function test_add_task_ids_filter_with_none_returns_entries_without_task(): void
    {
        // Arrange
        $task = Task::factory()->create();
        $timeEntryWithTask = TimeEntry::factory()->create([
            'task_id' => $task->getKey(),
            'organization_id' => $task->organization_id,
        ]);
        $timeEntryWithoutTask = TimeEntry::factory()->create([
            'task_id' => null,
        ]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act
        $filter->addTaskIdsFilter([TimeEntryFilter::NONE_VALUE]);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(1, $timeEntries);
        $this->assertTrue($timeEntries->contains($timeEntryWithoutTask));
        $this->assertFalse($timeEntries->contains($timeEntryWithTask));
    }

    public function test_add_client_ids_filter_with_none_returns_entries_without_client(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $timeEntryWithClient = TimeEntry::factory()->create([
            'client_id' => $client->getKey(),
            'organization_id' => $client->organization_id,
        ]);
        $timeEntryWithoutClient = TimeEntry::factory()->create([
            'client_id' => null,
        ]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act
        $filter->addClientIdsFilter([TimeEntryFilter::NONE_VALUE]);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(1, $timeEntries);
        $this->assertTrue($timeEntries->contains($timeEntryWithoutClient));
        $this->assertFalse($timeEntries->contains($timeEntryWithClient));
    }

    public function test_add_tag_ids_filter_with_none_returns_entries_without_tags(): void
    {
        // Arrange
        $tag = Tag::factory()->create();
        $timeEntryWithTag = TimeEntry::factory()->create([
            'tags' => [$tag->getKey()],
        ]);
        $timeEntryWithEmptyTags = TimeEntry::factory()->create([
            'tags' => [],
        ]);
        $timeEntryWithNullTags = TimeEntry::factory()->create([
            'tags' => null,
        ]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act
        $filter->addTagIdsFilter([TimeEntryFilter::NONE_VALUE]);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(2, $timeEntries);
        $this->assertTrue($timeEntries->contains($timeEntryWithEmptyTags));
        $this->assertTrue($timeEntries->contains($timeEntryWithNullTags));
        $this->assertFalse($timeEntries->contains($timeEntryWithTag));
    }

    public function test_add_tag_ids_filter_with_none_and_ids_returns_both(): void
    {
        // Arrange
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $timeEntryWithTag1 = TimeEntry::factory()->create([
            'tags' => [$tag1->getKey()],
        ]);
        $timeEntryWithTag2 = TimeEntry::factory()->create([
            'tags' => [$tag2->getKey()],
        ]);
        $timeEntryWithNoTags = TimeEntry::factory()->create([
            'tags' => [],
        ]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act
        $filter->addTagIdsFilter([$tag1->getKey(), TimeEntryFilter::NONE_VALUE]);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(2, $timeEntries);
        $this->assertTrue($timeEntries->contains($timeEntryWithTag1));
        $this->assertTrue($timeEntries->contains($timeEntryWithNoTags));
        $this->assertFalse($timeEntries->contains($timeEntryWithTag2));
    }

    public function test_add_tag_ids_filter_not_contains_includes_entries_without_matching_tag(): void
    {
        // Arrange
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $timeEntryWithTag1 = TimeEntry::factory()->create([
            'tags' => [$tag1->getKey()],
        ]);
        $timeEntryWithTag2 = TimeEntry::factory()->create([
            'tags' => [$tag2->getKey()],
        ]);
        $timeEntryWithAllTags = TimeEntry::factory()->create([
            'tags' => [$tag1->getKey(), $tag2->getKey()],
        ]);
        $timeEntryWithEmptyTags = TimeEntry::factory()->create([
            'tags' => [],
        ]);
        $timeEntryWithNullTags = TimeEntry::factory()->create([
            'tags' => null,
        ]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act
        $filter->addTagIdsFilter([$tag1->getKey()], TagMatchType::NotContains);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(3, $timeEntries);
        $this->assertFalse($timeEntries->contains($timeEntryWithTag1));
        $this->assertTrue($timeEntries->contains($timeEntryWithTag2));
        $this->assertFalse($timeEntries->contains($timeEntryWithAllTags));
        $this->assertTrue($timeEntries->contains($timeEntryWithEmptyTags));
        $this->assertTrue($timeEntries->contains($timeEntryWithNullTags));
    }

    public function test_add_tag_ids_filter_not_contains_with_none_excludes_entries_without_tags(): void
    {
        // Arrange
        $tag = Tag::factory()->create();
        $timeEntryWithTag = TimeEntry::factory()->create([
            'tags' => [$tag->getKey()],
        ]);
        $timeEntryWithEmptyTags = TimeEntry::factory()->create([
            'tags' => [],
        ]);
        $timeEntryWithNullTags = TimeEntry::factory()->create([
            'tags' => null,
        ]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act
        $filter->addTagIdsFilter([TimeEntryFilter::NONE_VALUE], TagMatchType::NotContains);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(1, $timeEntries);
        $this->assertTrue($timeEntries->contains($timeEntryWithTag));
        $this->assertFalse($timeEntries->contains($timeEntryWithEmptyTags));
        $this->assertFalse($timeEntries->contains($timeEntryWithNullTags));
    }

    public function test_add_tag_ids_filter_not_contains_with_multiple_tags_excludes_entries_with_any_of_them(): void
    {
        // Arrange
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $tag3 = Tag::factory()->create();
        $timeEntryWithTag1 = TimeEntry::factory()->create(['tags' => [$tag1->getKey()]]);
        $timeEntryWithTag2 = TimeEntry::factory()->create(['tags' => [$tag2->getKey()]]);
        $timeEntryWithTag3 = TimeEntry::factory()->create(['tags' => [$tag3->getKey()]]);
        // a filtered tag (tag1) mixed with an unrelated one (tag3): still excluded
        $timeEntryWithTag1AndTag3 = TimeEntry::factory()->create(['tags' => [$tag1->getKey(), $tag3->getKey()]]);
        $timeEntryWithoutTags = TimeEntry::factory()->create(['tags' => null]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act: "does not contain tag1 or tag2" (NOT (has tag1 OR has tag2))
        $filter->addTagIdsFilter([$tag1->getKey(), $tag2->getKey()], TagMatchType::NotContains);

        // Assert: only entries that have neither tag1 nor tag2 remain
        $timeEntries = $builder->get();
        $this->assertCount(2, $timeEntries);
        $this->assertFalse($timeEntries->contains($timeEntryWithTag1));
        $this->assertFalse($timeEntries->contains($timeEntryWithTag2));
        $this->assertTrue($timeEntries->contains($timeEntryWithTag3));
        $this->assertFalse($timeEntries->contains($timeEntryWithTag1AndTag3));
        $this->assertTrue($timeEntries->contains($timeEntryWithoutTags));
    }

    public function test_add_tag_ids_filter_contains_mode_returns_only_entries_with_tag(): void
    {
        // Arrange
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $timeEntryWithTag1 = TimeEntry::factory()->create(['tags' => [$tag1->getKey()]]);
        $timeEntryWithTag2 = TimeEntry::factory()->create(['tags' => [$tag2->getKey()]]);
        $timeEntryWithEmptyTags = TimeEntry::factory()->create(['tags' => []]);
        $timeEntryWithNullTags = TimeEntry::factory()->create(['tags' => null]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act: explicit contains mode
        $filter->addTagIdsFilter([$tag1->getKey()], TagMatchType::Contains);

        // Assert: only the entry that has tag1
        $timeEntries = $builder->get();
        $this->assertCount(1, $timeEntries);
        $this->assertTrue($timeEntries->contains($timeEntryWithTag1));
        $this->assertFalse($timeEntries->contains($timeEntryWithTag2));
        $this->assertFalse($timeEntries->contains($timeEntryWithEmptyTags));
        $this->assertFalse($timeEntries->contains($timeEntryWithNullTags));
    }

    public function test_add_tag_ids_filter_not_contains_with_none_and_tag_excludes_tagged_and_untagged(): void
    {
        // Arrange
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $timeEntryWithTag1 = TimeEntry::factory()->create(['tags' => [$tag1->getKey()]]);
        $timeEntryWithTag2 = TimeEntry::factory()->create(['tags' => [$tag2->getKey()]]);
        $timeEntryWithBothTags = TimeEntry::factory()->create(['tags' => [$tag1->getKey(), $tag2->getKey()]]);
        $timeEntryWithEmptyTags = TimeEntry::factory()->create(['tags' => []]);
        $timeEntryWithNullTags = TimeEntry::factory()->create(['tags' => null]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act: NOT (has tag1 OR has no tags) => has at least one tag and not tag1
        $filter->addTagIdsFilter([$tag1->getKey(), TimeEntryFilter::NONE_VALUE], TagMatchType::NotContains);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(1, $timeEntries);
        $this->assertFalse($timeEntries->contains($timeEntryWithTag1));
        $this->assertTrue($timeEntries->contains($timeEntryWithTag2));
        $this->assertFalse($timeEntries->contains($timeEntryWithBothTags));
        $this->assertFalse($timeEntries->contains($timeEntryWithEmptyTags));
        $this->assertFalse($timeEntries->contains($timeEntryWithNullTags));
    }

    public function test_add_tag_ids_filter_with_empty_array_applies_no_filter(): void
    {
        // Arrange
        $tag = Tag::factory()->create();
        TimeEntry::factory()->create(['tags' => [$tag->getKey()]]);
        TimeEntry::factory()->create(['tags' => []]);
        TimeEntry::factory()->create(['tags' => null]);

        // Act + Assert: an empty selection is no constraint in either mode
        $builderNotContains = TimeEntry::query();
        (new TimeEntryFilter($builderNotContains))->addTagIdsFilter([], TagMatchType::NotContains);
        $this->assertCount(3, $builderNotContains->get());

        $builderContains = TimeEntry::query();
        (new TimeEntryFilter($builderContains))->addTagIdsFilter([], TagMatchType::Contains);
        $this->assertCount(3, $builderContains->get());
    }

    public function test_add_tag_ids_filter_with_null_match_type_defaults_to_contains(): void
    {
        // Arrange
        $tag = Tag::factory()->create();
        $timeEntryWithTag = TimeEntry::factory()->create(['tags' => [$tag->getKey()]]);
        $timeEntryWithoutTag = TimeEntry::factory()->create(['tags' => null]);

        $builder = TimeEntry::query();
        $filter = new TimeEntryFilter($builder);

        // Act: a null match type falls back to "contains"
        $filter->addTagIdsFilter([$tag->getKey()], null);

        // Assert
        $timeEntries = $builder->get();
        $this->assertCount(1, $timeEntries);
        $this->assertTrue($timeEntries->contains($timeEntryWithTag));
        $this->assertFalse($timeEntries->contains($timeEntryWithoutTag));
    }
}
