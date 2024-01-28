<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Jetstream;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProjectEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string>  $permissions
     * @return object{user: User, organization: Organization}
     */
    private function createUserWithPermission(array $permissions): object
    {
        Jetstream::role('custom-test', 'Custom Test', $permissions)->description('Role custom for testing');
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->users()->attach($user, [
            'role' => 'custom-test',
        ]);

        return (object) [
            'user' => $user,
            'organization' => $organization,
        ];
    }

    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_projects(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $projects = Project::factory()->forOrganization($data->organization)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.projects.index', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(403);
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

    public function test_store_endpoint_fails_if_user_has_no_permission_to_create_projects(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $project = Project::factory()->forOrganization($data->organization)->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), $project->toArray());

        // Assert
        $response->assertStatus(403);
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
            'organization_id' => $project->organization_id,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(Project::class, [
            'name' => $project->name,
            'color' => $project->color,
            'organization_id' => $project->organization_id,
        ]);
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
        $response->assertStatus(403);
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
        $response->assertStatus(403);
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
        $response->assertStatus(200);
        $this->assertDatabaseMissing(Project::class, [
            'id' => $project->getKey(),
        ]);
    }
}
