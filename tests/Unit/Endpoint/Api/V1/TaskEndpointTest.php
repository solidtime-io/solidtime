<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Project;
use App\Models\Task;
use Laravel\Passport\Passport;

class TaskEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_tasks(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
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
        ]);
        $tasks = Task::factory()->forOrganization($data->organization)->createMany(4);
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

    public function test_store_endpoint_fails_if_user_has_no_permission_to_create_tags()
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
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

    public function test_store_endpoint_creates_new_task_if_user_has_permission_to_create_tasks()
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
            'organization_id' => $data->organization->id,
        ]);
    }

    public function test_update_endpoint_fails_if_user_has_no_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
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

    public function test_delete_endpoint_fails_if_user_has_no_permission_to_delete_tasks(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
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
