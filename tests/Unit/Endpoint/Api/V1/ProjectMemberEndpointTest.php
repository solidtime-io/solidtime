<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Laravel\Passport\Passport;

class ProjectMemberEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_project_members(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMembers = ProjectMember::factory()->forProject($project)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.project-members.index', [
            $data->organization->getKey(),
            $project->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_fails_if_the_project_does_not_belong_to_given_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:view',
        ]);
        $otherData = $this->createUserWithPermission([
            'project-members:view',
        ]);
        $project = Project::factory()->forOrganization($otherData->organization)->create();
        $projectMembers = ProjectMember::factory()->forProject($project)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.project-members.index', [
            $data->organization->getKey(),
            $project->getKey(),
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_list_of_all_project_members_of_a_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:view',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMembers = ProjectMember::factory()->forProject($project)->createMany(4);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.project-members.index', [
            $data->organization->getKey(),
            $project->getKey(),
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data');
    }

    public function test_store_endpoint_fails_if_user_has_no_permission_to_add_members_to_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMemberFake = ProjectMember::factory()->make();
        $user = User::factory()->attachToOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'user_id' => $user->getKey(),
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_if_given_project_does_not_belong_to_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:create',
        ]);
        $otherData = $this->createUserWithPermission([
            'project-members:create',
        ]);
        $project = Project::factory()->forOrganization($otherData->organization)->create();
        $projectMemberFake = ProjectMember::factory()->make();
        $user = User::factory()->attachToOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'user_id' => $user->getKey(),
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_if_given_user_does_not_belong_to_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:create',
        ]);
        $otherData = $this->createUserWithPermission([
            'project-members:create',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMemberFake = ProjectMember::factory()->make();
        $user = User::factory()->attachToOrganization($otherData->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'user_id' => $user->getKey(),
        ]);

        // Assert
        $response->assertInvalid(['user_id']);
    }

    public function test_store_endpoint_fails_if_user_is_a_placeholder(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:create',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMemberFake = ProjectMember::factory()->make();
        $user = User::factory()->attachToOrganization($data->organization)->placeholder()->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'user_id' => $user->getKey(),
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'inactive_user_can_not_be_used',
            'message' => 'Inactive user can not be used',
        ]);
        $this->assertDatabaseMissing(ProjectMember::class, [
            'billable_rate' => $projectMemberFake->billable_rate,
            'user_id' => $user->getKey(),
            'project_id' => $project->getKey(),
        ]);
    }

    public function test_store_endpoint_fails_if_user_is_already_member_of_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:create',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMemberFake = ProjectMember::factory()->make();
        $user = User::factory()->attachToOrganization($data->organization)->create();
        ProjectMember::factory()->forProject($project)->forUser($user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'user_id' => $user->getKey(),
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertExactJson([
            'error' => true,
            'key' => 'user_is_already_member_of_project',
            'message' => 'User is already a member of the project',
        ]);
        $this->assertDatabaseMissing(ProjectMember::class, [
            'billable_rate' => $projectMemberFake->billable_rate,
            'user_id' => $user->getKey(),
            'project_id' => $project->getKey(),
        ]);
    }

    public function test_store_endpoint_creates_new_project_member(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:create',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMemberFake = ProjectMember::factory()->make();
        $user = User::factory()->attachToOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'user_id' => $user->getKey(),
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(ProjectMember::class, [
            'billable_rate' => $projectMemberFake->billable_rate,
            'user_id' => $user->getKey(),
            'project_id' => $project->getKey(),
        ]);
    }

    public function test_update_endpoint_fails_if_project_member_is_not_part_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:update',
        ]);
        $otherData = $this->createUserWithPermission([
            'project-members:update',
        ]);
        $project = Project::factory()->forOrganization($otherData->organization)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->create();
        $projectMemberFake = ProjectMember::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.project-members.update', [$data->organization->getKey(), $projectMember->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
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
        $projectMember = ProjectMember::factory()->forProject($project)->create();
        $projectMemberFake = ProjectMember::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.project-members.update', [$data->organization->getKey(), $projectMember->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_updates_project_member(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->create();
        $projectMemberFake = ProjectMember::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.project-members.update', [$data->organization->getKey(), $projectMember->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(ProjectMember::class, [
            'id' => $projectMember->getKey(),
            'billable_rate' => $projectMemberFake->billable_rate,
            'user_id' => $projectMember->user_id,
        ]);
    }

    public function test_destroy_endpoint_fails_if_user_is_not_part_of_project_members_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:delete',
        ]);
        $otherData = $this->createUserWithPermission([
            'project-members:delete',
        ]);
        $project = Project::factory()->forOrganization($otherData->organization)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.project-members.destroy', [$data->organization->getKey(), $projectMember->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(ProjectMember::class, [
            'id' => $projectMember->getKey(),
        ]);
    }

    public function test_destroy_endpoint_fails_if_user_has_no_permission_to_delete_projects(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.project-members.destroy', [$data->organization->getKey(), $projectMember->getKey()]));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(ProjectMember::class, [
            'id' => $projectMember->getKey(),
        ]);
    }

    public function test_destroy_endpoint_deletes_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:delete',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.project-members.destroy', [$data->organization->getKey(), $projectMember->getKey()]));

        // Assert
        $response->assertStatus(204);
        $response->assertNoContent();
        $this->assertDatabaseMissing(ProjectMember::class, [
            'id' => $projectMember->getKey(),
        ]);
    }
}
