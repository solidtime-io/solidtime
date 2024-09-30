<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\TaskController;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(TaskController::class)]
class TaskEndpointTest extends ApiEndpointTestAbstract
{
    public function test_non_valid_uuid_for_organization_id_fails(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:view',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', ['invalid-uuid']));

        // Assert
        $response->assertStatus(404);
    }

    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_tasks(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Task::factory()->forOrganization($data->organization)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_validation_fails_if_project_id_is_not_pat(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Task::factory()->forOrganization($data->organization)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_list_of_all_tasks_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:view',
            'tasks:view:all',
        ]);
        $tasks = Task::factory()->forOrganization($data->organization)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
    }

    public function test_index_endpoint_without_filter_done_returns_list_of_all_tasks_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:view',
            'tasks:view:all',
        ]);
        $notDoneTasks = Task::factory()->forOrganization($data->organization)->createMany(2);
        $doneTasks = Task::factory()->forOrganization($data->organization)->isDone()->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing($notDoneTasks->pluck('id')->toArray(), $response->json('data.*.id'));
    }

    public function test_index_endpoint_with_filter_done_true_returns_list_of_all_done_tasks_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:view',
            'tasks:view:all',
        ]);
        $notDoneTasks = Task::factory()->forOrganization($data->organization)->createMany(2);
        $doneTasks = Task::factory()->forOrganization($data->organization)->isDone()->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [$data->organization->getKey(), 'done' => 'true']));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing($doneTasks->pluck('id')->toArray(), $response->json('data.*.id'));
    }

    public function test_index_endpoint_with_filter_done_false_returns_list_of_all_not_done_tasks_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:view',
            'tasks:view:all',
        ]);
        $notDoneTasks = Task::factory()->forOrganization($data->organization)->createMany(2);
        $doneTasks = Task::factory()->forOrganization($data->organization)->isDone()->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [$data->organization->getKey(), 'done' => 'false']));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing($notDoneTasks->pluck('id')->toArray(), $response->json('data.*.id'));
    }

    public function test_index_endpoint_with_filter_done_all_returns_list_of_all_tasks_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:view',
            'tasks:view:all',
        ]);
        $notDoneTasks = Task::factory()->forOrganization($data->organization)->createMany(2);
        $doneTasks = Task::factory()->forOrganization($data->organization)->isDone()->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [$data->organization->getKey(), 'done' => 'all']));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
    }

    public function test_index_endpoint_returns_list_of_all_tasks_with_access_of_organization_if_user_has_no_all_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:view',
        ]);
        $otherProject = Project::factory()->create();
        Task::factory()->forOrganization($data->organization)->forProject($otherProject)->createMany(4);
        $projectPublic = Project::factory()->isPublic()->create();
        Task::factory()->forOrganization($data->organization)->forProject($projectPublic)->createMany(2);
        $projectAsMember = Project::factory()->isPrivate()->create();
        ProjectMember::factory()->forProject($projectAsMember)->forMember($data->member)->create();
        Task::factory()->forOrganization($data->organization)->forProject($projectAsMember)->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
    }

    public function test_index_endpoint_returns_list_of_all_tasks_of_organization_filtered_by_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:view',
            'tasks:view:all',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        Task::factory()->forOrganization($data->organization)->createMany(4);
        Task::factory()->forOrganization($data->organization)->forProject($project)->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [
            $data->organization->getKey(),
            'project_id' => $project->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_index_endpoint_validation_fails_if_project_id_does_not_belong_to_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:view',
        ]);
        $otherData = $this->createUserWithPermission([
            'tasks:view',
        ]);
        $project = Project::factory()->forOrganization($otherData->organization)->create();
        Task::factory()->forOrganization($data->organization)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [
            $data->organization->getKey(),
            'project_id' => $project->getKey(),
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertInvalid([
            'project_id',
        ]);
    }

    public function test_index_endpoint_validation_fails_if_project_is_not_visible_by_user_and_user_does_not_have_tasks_all_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:view',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        Task::factory()->forOrganization($data->organization)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [
            $data->organization->getKey(),
            'project_id' => $project->getKey(),
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertInvalid([
            'project_id',
        ]);
    }

    public function test_index_endpoint_returns_list_of_all_tasks_of_organization_filtered_by_project_if_user_has_access_to_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:view',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        ProjectMember::factory()->forProject($project)->forMember($data->member)->create();
        Task::factory()->forOrganization($data->organization)->createMany(4);
        Task::factory()->forOrganization($data->organization)->forProject($project)->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.tasks.index', [
            $data->organization->getKey(),
            'project_id' => $project->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_store_endpoint_fails_if_user_has_no_permission_to_create_tasks(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $project = Project::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.tasks.store', [$data->organization->getKey()]), [
            'name' => 'Task 1',
            'project_id' => $project->getKey(),
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseMissing(Task::class, [
            'name' => 'Task 1',
        ]);
    }

    public function test_store_endpoint_fails_if_task_with_same_name_already_exists_in_same_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:create',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $task = Task::factory()->forOrganization($data->organization)->forProject($project)->create([
            'name' => 'Task 1',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.tasks.store', [$data->organization->getKey()]), [
            'name' => $task->name,
            'project_id' => $project->getKey(),
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => 'A task with the same name already exists in the project.',
        ]);
    }

    public function test_store_endpoint_creates_new_task_even_if_task_with_same_name_already_exists_in_other_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:create',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $otherProject = Project::factory()->forOrganization($data->organization)->create();
        $task = Task::factory()->forOrganization($data->organization)->forProject($otherProject)->create([
            'name' => 'Task 1',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.tasks.store', [$data->organization->getKey()]), [
            'name' => $task->name,
            'project_id' => $project->getKey(),
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(Task::class, [
            'name' => $task->name,
            'project_id' => $project->getKey(),
            'organization_id' => $data->organization->getKey(),
        ]);
    }

    public function test_store_endpoint_creates_new_task_if_user_has_permission_to_create_tasks(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:create',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.tasks.store', [$data->organization->getKey()]), [
            'name' => 'Task 1',
            'project_id' => $project->getKey(),
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(Task::class, [
            'name' => 'Task 1',
            'project_id' => $project->getKey(),
            'organization_id' => $data->organization->getKey(),
        ]);
    }

    public function test_store_endpoint_ignores_estimated_time_if_pro_features_are_disabled(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:create',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.tasks.store', [$data->organization->getKey()]), [
            'name' => 'Task 1',
            'project_id' => $project->getKey(),
            'estimated_time' => 3600,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', 'Task 1')
            ->where('data.project_id', $project->getKey())
            ->where('data.estimated_time', null)
        );
        $this->assertDatabaseHas(Task::class, [
            'name' => 'Task 1',
            'project_id' => $project->getKey(),
            'organization_id' => $data->organization->getKey(),
            'estimated_time' => null,
        ]);
    }

    public function test_store_endpoint_can_store_with_estimated_time_with_pro_features_enabled(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:create',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);
        $this->actAsOrganizationWithSubscription();

        // Act
        $response = $this->postJson(route('api.v1.tasks.store', [$data->organization->getKey()]), [
            'name' => 'Task 1',
            'project_id' => $project->getKey(),
            'estimated_time' => 3600,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', 'Task 1')
            ->where('data.project_id', $project->getKey())
            ->where('data.estimated_time', 3600)
        );
        $this->assertDatabaseHas(Task::class, [
            'name' => 'Task 1',
            'project_id' => $project->getKey(),
            'organization_id' => $data->organization->getKey(),
            'estimated_time' => 3600,
        ]);
    }

    public function test_update_endpoint_fails_if_user_has_no_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $task = Task::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.tasks.update', [$data->organization->getKey(), $task->getKey()]), [
            'name' => 'Updated Task',
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Task::class, [
            'id' => $task->getKey(),
            'name' => $task->name,
        ]);
        $this->assertDatabaseMissing(Task::class, [
            'id' => $task->getKey(),
            'name' => 'Updated Task',
        ]);
    }

    public function test_update_endpoint_fails_if_task_with_same_name_already_exists_in_same_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $name = 'Task 1';
        $task = Task::factory()->forProject($project)->forOrganization($data->organization)->create([
            'name' => $name,
        ]);
        $otherTask = Task::factory()->forProject($project)->forOrganization($data->organization)->create([
            'name' => $name,
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.tasks.update', [$data->organization->getKey(), $task->getKey()]), [
            'name' => $name,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => 'A task with the same name already exists in the project.',
        ]);
    }

    public function test_update_endpoint_updates_task_if_task_with_same_name_already_exists_in_other_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $otherProject = Project::factory()->forOrganization($data->organization)->create();
        $name = 'Task 1';
        $task = Task::factory()->forProject($project)->forOrganization($data->organization)->create([
            'name' => $name,
        ]);
        $otherTask = Task::factory()->forProject($otherProject)->forOrganization($data->organization)->create([
            'name' => $name,
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.tasks.update', [$data->organization->getKey(), $task->getKey()]), [
            'name' => $name,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Task::class, [
            'id' => $task->getKey(),
            'name' => $name,
        ]);
    }

    public function test_update_endpoint_updates_task_if_user_has_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:update',
        ]);
        $task = Task::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.tasks.update', [$data->organization->getKey(), $task->getKey()]), [
            'name' => 'Updated Task',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Task::class, [
            'id' => $task->getKey(),
            'name' => 'Updated Task',
        ]);
    }

    public function test_update_endpoint_can_set_task_to_done(): void
    {
        // Arrange
        $now = Carbon::now();
        $this->travelTo($now);
        $data = $this->createUserWithPermission([
            'tasks:update',
        ]);
        $task = Task::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.tasks.update', [$data->organization->getKey(), $task->getKey()]), [
            'name' => $task->name,
            'is_done' => true,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Task::class, [
            'id' => $task->getKey(),
            'done_at' => $now->toDateTimeString(),
        ]);
    }

    public function test_update_endpoint_can_set_task_to_not_done(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:update',
        ]);
        $task = Task::factory()->forOrganization($data->organization)->isDone()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.tasks.update', [$data->organization->getKey(), $task->getKey()]), [
            'name' => $task->name,
            'is_done' => false,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Task::class, [
            'id' => $task->getKey(),
            'done_at' => null,
        ]);
    }

    public function test_update_endpoint_ignores_estimated_time_if_pro_features_are_disabled(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:update',
        ]);
        $task = Task::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.tasks.update', [$data->organization->getKey(), $task->getKey()]), [
            'name' => $task->name,
            'estimated_time' => 3600,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', $task->name)
            ->where('data.estimated_time', null)
        );
        $this->assertDatabaseHas(Task::class, [
            'id' => $task->getKey(),
            'estimated_time' => null,
        ]);
    }

    public function test_update_endpoint_can_update_estimated_time_with_pro_features_enabled(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:update',
        ]);
        $task = Task::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);
        $this->actAsOrganizationWithSubscription();

        // Act
        $response = $this->putJson(route('api.v1.tasks.update', [$data->organization->getKey(), $task->getKey()]), [
            'name' => $task->name,
            'estimated_time' => 3600,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', $task->name)
            ->where('data.estimated_time', 3600)
        );
        $this->assertDatabaseHas(Task::class, [
            'id' => $task->getKey(),
            'estimated_time' => 3600,
        ]);
    }

    public function test_delete_endpoint_deletes_tasks_if_user_has_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:delete',
        ]);
        $task = Task::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.tasks.destroy', [$data->organization->getKey(), $task->getKey()]));

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing(Task::class, [
            'id' => $task->getKey(),
        ]);
    }

    public function test_destroy_endpoint_fails_if_task_is_still_in_use_by_a_time_entry(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:delete',
        ]);
        $task = Task::factory()->forOrganization($data->organization)->create();
        TimeEntry::factory()->forMember($data->member)->forTask($task)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.tasks.destroy', [$data->organization->getKey(), $task->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'The task is still used by a time entry and can not be deleted.');
        $this->assertDatabaseHas(Task::class, [
            'id' => $task->getKey(),
        ]);
    }

    public function test_delete_endpoint_fails_if_user_has_no_permission_to_delete_tasks(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $task = Task::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.tasks.destroy', [$data->organization->getKey(), $task->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Task::class, [
            'id' => $task->getKey(),
        ]);
    }

    public function test_delete_endpoint_fails_if_task_does_not_belong_to_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'tasks:delete',
        ]);
        $otherData = $this->createUserWithPermission([
            'tasks:delete',
        ]);
        $task = Task::factory()->forOrganization($otherData->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.tasks.destroy', [$data->organization->getKey(), $task->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Task::class, [
            'id' => $task->getKey(),
        ]);
    }
}
