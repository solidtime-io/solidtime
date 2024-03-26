<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use Laravel\Passport\Passport;

class ProjectEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_projects(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $projects = Project::factory()->forOrganization($data->organization)->createMany(4);
        $projectsWithClients = Project::factory()->forOrganization($data->organization)->withClient()->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.projects.index', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_list_of_all_projects_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:view',
        ]);
        $projects = Project::factory()->forOrganization($data->organization)->createMany(4);
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
        $data = $this->createUserWithPermission([
        ]);
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
        $data = $this->createUserWithPermission([
        ]);
        $projectFake = Project::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
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
        $project = Project::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), [
            'name' => $project->name,
            'color' => $project->color,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(Project::class, [
            'name' => $project->name,
            'color' => $project->color,
            'organization_id' => $project->organization_id,
        ]);
    }

    public function test_store_endpoint_creates_new_project_with_client(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:create',
        ]);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $project = Project::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), [
            'name' => $project->name,
            'color' => $project->color,
            'client_id' => $client->getKey(),
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(Project::class, [
            'name' => $project->name,
            'color' => $project->color,
            'organization_id' => $project->organization_id,
            'client_id' => $client->getKey(),
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
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_projects(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectFake = Project::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_updates_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectFake = Project::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Project::class, [
            'name' => $projectFake->name,
            'color' => $projectFake->color,
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
        $data = $this->createUserWithPermission([
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.projects.destroy', [$data->organization->getKey(), $project->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_destroy_endpoint_deletes_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:delete',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.projects.destroy', [$data->organization->getKey(), $project->getKey()]));

        // Assert
        $response->assertStatus(204);
        $response->assertNoContent();
        $this->assertDatabaseMissing(Project::class, [
            'id' => $project->getKey(),
        ]);
    }
}
