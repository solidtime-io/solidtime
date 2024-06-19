<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\ProjectController;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Service\BillableRateService;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(ProjectController::class)]
class ProjectEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_projects(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $projects = Project::factory()->forOrganization($data->organization)->createMany(4);
        $projectsWithClients = Project::factory()->forOrganization($data->organization)->withClient()->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.projects.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_list_of_all_projects_of_organization_for_user_with_all_projects_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:view',
            'projects:view:all',
        ]);
        $projects = Project::factory()->forOrganization($data->organization)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.projects.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
    }

    public function test_index_endpoint_returns_list_of_projects_of_organization_which_are_public_or_where_user_is_member_for_user_with_restricted_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:view',
        ]);
        $privateProjects = Project::factory()->forOrganization($data->organization)->isPrivate()->createMany(2);
        $publicProjects = Project::factory()->forOrganization($data->organization)->isPublic()->createMany(2);
        $privateProjectsWithMembership = Project::factory()->forOrganization($data->organization)->addMember($data->member)->isPrivate()->createMany(2);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.projects.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
    }

    public function test_show_endpoint_fails_if_user_is_not_part_of_project_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:view',
        ]);
        $otherOrganization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.projects.show', [$data->organization->getKey(), $project->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_fails_if_user_has_no_permission_to_view_projects(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $project = Project::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.projects.show', [$data->organization->getKey(), $project->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_returns_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:view',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.projects.show', [$data->organization->getKey(), $project->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $project->getKey());
    }

    public function test_store_endpoint_fails_if_user_has_no_permission_to_create_projects(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $projectFake = Project::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_creates_new_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:create',
        ]);
        $projectFake = Project::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(Project::class, [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'organization_id' => $projectFake->organization_id,
            'is_billable' => $projectFake->is_billable,
        ]);
    }

    public function test_store_endpoint_fails_if_name_is_already_used_in_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:create',
        ]);
        $name = 'Project Name';
        $project = Project::factory()->forOrganization($data->organization)->create([
            'name' => $name,
        ]);
        $projectFake = Project::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), [
            'name' => $name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => 'A project with the same name already exists in the organization.',
        ]);
    }

    public function test_store_endpoint_creates_project_if_name_is_used_in_other_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:create',
        ]);
        $name = 'Project Name';
        $otherOrganization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($otherOrganization)->create([
            'name' => $name,
        ]);
        $projectFake = Project::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), [
            'name' => $name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(Project::class, [
            'name' => $name,
            'color' => $projectFake->color,
            'organization_id' => $data->organization->getKey(),
            'is_billable' => $projectFake->is_billable,
        ]);
    }

    public function test_store_endpoint_creates_new_project_with_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:create',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $projectFake = Project::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
            'client_id' => $client->getKey(),
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(Project::class, [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
            'organization_id' => $projectFake->organization_id,
            'client_id' => $client->getKey(),
        ]);
    }

    public function test_store_endpoint_creates_new_project_with_billable_rate(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:create',
        ]);
        $projectFake = Project::factory()->forOrganization($data->organization)->make();
        $this->assertBillableRateServiceIsUnused();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'is_billable' => true,
            'billable_rate' => 10001,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(Project::class, [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'is_billable' => true,
            'billable_rate' => 10001,
            'organization_id' => $projectFake->organization_id,
        ]);
    }

    public function test_update_endpoint_fails_if_user_is_not_part_of_project_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:update',
        ]);
        $otherOrganization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($otherOrganization)->create();
        $projectFake = Project::factory()->make();
        $this->assertBillableRateServiceIsUnused();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_projects(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectFake = Project::factory()->make();
        $this->assertBillableRateServiceIsUnused();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_fails_if_name_is_already_used_in_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:update',
        ]);
        $name = 'Project Name';
        $projectWithTheName = Project::factory()->forOrganization($data->organization)->create([
            'name' => $name,
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectFake = Project::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => $name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => 'A project with the same name already exists in the organization.',
        ]);
    }

    public function test_update_endpoint_updates_project_if_name_is_used_in_other_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:update',
        ]);
        $name = 'Project Name';
        $otherOrganization = Organization::factory()->create();
        $otherProject = Project::factory()->forOrganization($otherOrganization)->create([
            'name' => $name,
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create([
            'name' => $name,
        ]);
        $projectFake = Project::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => $name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Project::class, [
            'name' => $name,
            'color' => $projectFake->color,
            'organization_id' => $data->organization->getKey(),
            'is_billable' => $projectFake->is_billable,
        ]);
    }

    public function test_update_endpoint_updates_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectFake = Project::factory()->make();
        $client = Client::factory()->forOrganization($data->organization)->create();
        $this->assertBillableRateServiceIsUnused();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
            'client_id' => $client->getKey(),
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Project::class, [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'client_id' => $client->getKey(),
        ]);
    }

    public function test_update_endpoint_can_update_projects_billable_rate(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectFake = Project::factory()->make();
        $this->assertBillableRateServiceIsUnused();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
            'billable_rate' => 10002,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Project::class, [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'billable_rate' => 10002,
        ]);
    }

    public function test_update_endpoint_can_update_projects_billable_rate_and_update_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectFake = Project::factory()->make();
        $this->mock(BillableRateService::class, function (MockInterface $mock) use ($project): void {
            $mock->shouldReceive('updateTimeEntriesBillableRateForProject')
                ->once()
                ->withArgs(fn (Project $projectArg) => $projectArg->is($project) && $projectArg->billable_rate === 10003);
        });
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'is_billable' => $projectFake->is_billable,
            'billable_rate' => 10003,
            'billable_rate_update_time_entries' => 'true',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Project::class, [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
            'billable_rate' => 10003,
        ]);
    }

    public function test_destroy_endpoint_fails_if_user_is_not_part_of_project_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:delete',
        ]);
        $otherOrganization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.projects.destroy', [$data->organization->getKey(), $project->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_fails_if_user_has_no_permission_to_delete_projects(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $project = Project::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.projects.destroy', [$data->organization->getKey(), $project->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_fails_if_project_is_still_in_use_by_a_task(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:delete',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $task = Task::factory()->forProject($project)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.projects.destroy', [$data->organization->getKey(), $project->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'The project is still used by a task and can not be deleted.');
        $this->assertDatabaseHas(Project::class, [
            'id' => $project->getKey(),
        ]);
    }

    public function test_destroy_endpoint_fails_if_project_is_still_in_use_by_a_time_entry(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:delete',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $timeEntry = TimeEntry::factory()->forProject($project)->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.projects.destroy', [$data->organization->getKey(), $project->getKey()]));

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'The project is still used by a time entry and can not be deleted.');
        $this->assertDatabaseHas(Project::class, [
            'id' => $project->getKey(),
        ]);
    }

    public function test_destroy_endpoint_deletes_project_with_project_members(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:delete',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMember = ProjectMember::factory()->forMember($data->member)->forProject($project)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.projects.destroy', [$data->organization->getKey(), $project->getKey()]));

        // Assert
        $response->assertStatus(204);
        $response->assertNoContent();
        $this->assertDatabaseMissing(Project::class, [
            'id' => $project->getKey(),
        ]);
        $this->assertDatabaseMissing(ProjectMember::class, [
            'id' => $projectMember->getKey(),
        ]);
    }
}
