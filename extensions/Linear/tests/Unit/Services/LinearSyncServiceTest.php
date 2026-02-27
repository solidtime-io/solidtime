<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Services;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Extensions\Linear\Services\LinearSyncService;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(LinearSyncService::class)]
class LinearSyncServiceTest extends TestCaseWithDatabase
{
    public function test_upsert_task_creates_new_task(): void
    {
        $organization = Organization::factory()->create();

        $service = new LinearSyncService();
        $task = $service->upsertTask($organization, [
            'id' => 'linear-issue-abc',
            'title' => 'Fix the bug',
            'state' => ['type' => 'started'],
            'project' => null,
            'estimate' => null,
        ]);

        $this->assertDatabaseHas('tasks', [
            'linear_id' => 'linear-issue-abc',
            'name' => 'Fix the bug',
            'organization_id' => $organization->getKey(),
        ]);
        $this->assertNull($task->done_at);
        $this->assertNotNull($task->project_id);
        $this->assertDatabaseHas('projects', [
            'name' => 'Linear',
            'organization_id' => $organization->getKey(),
        ]);
    }

    public function test_upsert_task_updates_existing_task(): void
    {
        $organization = Organization::factory()->create();

        $service = new LinearSyncService();
        $service->upsertTask($organization, [
            'id' => 'linear-issue-update',
            'title' => 'Original title',
            'state' => ['type' => 'started'],
            'project' => null,
            'estimate' => null,
        ]);

        $service->upsertTask($organization, [
            'id' => 'linear-issue-update',
            'title' => 'Updated title',
            'state' => ['type' => 'started'],
            'project' => null,
            'estimate' => 2.5,
        ]);

        $this->assertDatabaseCount('tasks', 1);
        $task = Task::where('linear_id', 'linear-issue-update')->first();
        $this->assertEquals('Updated title', $task->name);
        $this->assertEquals(9000, $task->estimated_time);
    }

    public function test_upsert_task_sets_done_at_for_completed_state(): void
    {
        $organization = Organization::factory()->create();

        $service = new LinearSyncService();
        $service->upsertTask($organization, [
            'id' => 'linear-issue-done',
            'title' => 'Done task',
            'state' => ['type' => 'completed'],
            'project' => null,
            'estimate' => null,
        ]);

        $task = Task::where('linear_id', 'linear-issue-done')->first();
        $this->assertNotNull($task->done_at);
    }

    public function test_upsert_task_links_to_project_by_linear_project_id(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create([
            'linear_project_id' => 'linear-proj-xyz',
        ]);

        $service = new LinearSyncService();
        $service->upsertTask($organization, [
            'id' => 'linear-issue-linked',
            'title' => 'Linked task',
            'state' => ['type' => 'started'],
            'project' => ['id' => 'linear-proj-xyz', 'name' => 'My Project'],
            'estimate' => null,
        ]);

        $task = Task::where('linear_id', 'linear-issue-linked')->first();
        $this->assertEquals($project->getKey(), $task->project_id);
    }

    public function test_upsert_task_creates_project_if_not_exists(): void
    {
        $organization = Organization::factory()->create();

        $service = new LinearSyncService();
        $service->upsertTask($organization, [
            'id' => 'linear-issue-new-proj',
            'title' => 'Task with new project',
            'state' => ['type' => 'unstarted'],
            'project' => ['id' => 'linear-proj-new', 'name' => 'New Linear Project'],
            'estimate' => null,
        ]);

        $this->assertDatabaseHas('projects', [
            'linear_project_id' => 'linear-proj-new',
            'name' => 'New Linear Project',
            'organization_id' => $organization->getKey(),
        ]);

        $task = Task::where('linear_id', 'linear-issue-new-proj')->first();
        $this->assertNotNull($task->project_id);
    }
}
