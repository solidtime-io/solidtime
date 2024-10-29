<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(TimeEntry::class)]
#[UsesClass(TimeEntry::class)]
class TimeEntryModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_a_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $timeEntry = TimeEntry::factory()->forUser($user)->create();

        // Act
        $timeEntry->refresh();
        $userRel = $timeEntry->user;

        // Assert
        $this->assertNotNull($userRel);
        $this->assertTrue($userRel->is($user));
    }

    public function test_it_belongs_to_a_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $timeEntry = TimeEntry::factory()->forOrganization($organization)->create();

        // Act
        $timeEntry->refresh();
        $organizationRel = $timeEntry->organization;

        // Assert
        $this->assertNotNull($organizationRel);
        $this->assertTrue($organizationRel->is($organization));
    }

    public function test_it_can_belong_to_a_project(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $timeEntry = TimeEntry::factory()->forProject($project)->create();

        // Act
        $timeEntry->refresh();
        $projectRel = $timeEntry->project;

        // Assert
        $this->assertNotNull($projectRel);
        $this->assertTrue($projectRel->is($project));
    }

    public function test_it_can_belong_to_no_project(): void
    {
        // Arrange
        $timeEntry = TimeEntry::factory()->forProject(null)->create();

        // Act
        $timeEntry->refresh();
        $project = $timeEntry->project;

        // Assert
        $this->assertNull($project);
    }

    public function test_it_can_belong_to_a_task(): void
    {
        // Arrange
        $task = Task::factory()->create();
        $timeEntry = TimeEntry::factory()->forTask($task)->create();

        // Act
        $timeEntry->refresh();
        $taskRel = $timeEntry->task;

        // Assert
        $this->assertNotNull($taskRel);
        $this->assertTrue($taskRel->is($task));
    }

    public function test_it_can_belong_to_no_task(): void
    {
        // Arrange
        $timeEntry = TimeEntry::factory()->forTask(null)->create();

        // Act
        $timeEntry->refresh();
        $taskRel = $timeEntry->task;

        // Assert
        $this->assertNull($taskRel);
    }

    public function test_eloquent_datetime_columns_remove_timezone_information_during_save(): void
    {
        // Arrange
        $timeEntry = TimeEntry::factory()->forTask(null)->create();

        // Act
        $timeEntry->start = Carbon::create(2021, 1, 1, 12, 0, 0, 'UTC')->timezone('+1');
        $timeEntry->save();

        // Assert
        $timeEntry->refresh();
        $this->assertSame('UTC', $timeEntry->start->getTimezone()->toRegionName());
        $this->assertSame('2021-01-01 13:00:00', $timeEntry->start->toDateTimeString());
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $timeEntry->getKey(),
            'start' => '2021-01-01 13:00:00',
        ]);
    }

    public function test_scope_has_tag_filter_by_tag(): void
    {
        // Arrange
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $timeEntry1 = TimeEntry::factory()->create([
            'tags' => [$tag1->getKey()],
        ]);
        $timeEntry2 = TimeEntry::factory()->create([
            'tags' => [$tag2->getKey()],
        ]);
        $timeEntry3 = TimeEntry::factory()->create([
            'tags' => ['something-else'],
        ]);
        $timeEntry4 = TimeEntry::factory()->create([
            'tags' => null,
        ]);

        // Act
        $result = TimeEntry::hasTag($tag1)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($timeEntry1));
    }

    public function test_computed_client_id_returns_null_when_no_project_is_assigned(): void
    {
        // Arrange
        $timeEntry = TimeEntry::factory()->forProject(null)->create();
        $timeEntry->client_id = null;
        $timeEntry->save();

        // Act
        $timeEntry->setComputedAttributeValue('client_id');
        $clientId = $timeEntry->client_id;

        // Assert
        $this->assertNull($clientId);
    }

    public function test_computed_client_id_returns_project_client_id(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $timeEntry = TimeEntry::factory()->forProject($project)->create();
        $timeEntry->client_id = null;
        $timeEntry->save();

        // Act
        $timeEntry->setComputedAttributeValue('client_id');
        $clientId = $timeEntry->client_id;

        // Assert
        $this->assertSame($project->client_id, $clientId);
    }

    public function test_has_many_tags_via_json_relation(): void
    {
        // Arrange
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $timeEntry = TimeEntry::factory()->create([
            'tags' => [$tag1->getKey(), $tag2->getKey()],
        ]);

        // Act
        $timeEntry->refresh();
        $tags = $timeEntry->tagsRelation;

        // Assert
        $this->assertCount(2, $tags);
        $this->assertTrue($tags->contains($tag1));
        $this->assertTrue($tags->contains($tag2));
    }

    public function test_has_many_tags_via_json_relation_eager_loaded(): void
    {
        // Arrange
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $timeEntry1 = TimeEntry::factory()->create([
            'tags' => [$tag1->getKey(), $tag2->getKey()],
            'created_at' => Carbon::now()->subDay(),
        ]);
        $timeEntry2 = TimeEntry::factory()->create([
            'tags' => [$tag1->getKey()],
            'created_at' => Carbon::now()->subDays(2),
        ]);

        // Act
        $timeEntries = TimeEntry::with('tagsRelation')->orderBy('created_at', 'desc')->get();
        $tags1 = $timeEntries->get(0)->tagsRelation;
        $tags2 = $timeEntries->get(1)->tagsRelation;

        // Assert
        $this->assertCount(2, $tags1);
        $this->assertTrue($tags1->contains($tag1));
        $this->assertTrue($tags1->contains($tag2));
        $this->assertCount(1, $tags2);
        $this->assertTrue($tags2->contains($tag1));
    }
}
