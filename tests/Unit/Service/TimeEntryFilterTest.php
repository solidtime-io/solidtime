<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Models\Client;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Service\TimeEntryFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(TimeEntryFilter::class)]
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
}
