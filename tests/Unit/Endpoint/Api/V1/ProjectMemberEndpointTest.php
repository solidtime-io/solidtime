<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\ProjectMemberController;
use App\Models\Member;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Service\BillableRateService;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(ProjectMemberController::class)]
class ProjectMemberEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission_to_view_project_members(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
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
        $data = $this->createUserWithPermission();
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMemberFake = ProjectMember::factory()->make();
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'member_id' => $member->getKey(),
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
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'member_id' => $member->getKey(),
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
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($otherData->organization)->forUser($user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'member_id' => $member->getKey(),
        ]);

        // Assert
        $response->assertInvalid(['member_id']);
    }

    public function test_store_endpoint_fails_if_user_is_a_placeholder(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:create',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMemberFake = ProjectMember::factory()->make();
        $user = User::factory()->placeholder()->create();
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'member_id' => $member->getKey(),
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
            'member_id' => $member->getKey(),
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
        $member = Member::factory()->forOrganization($data->organization)->create();
        ProjectMember::factory()->forProject($project)->forMember($member)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'member_id' => $member->getKey(),
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
            'member_id' => $member->getKey(),
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
        $user = User::factory()->create();
        $member = Member::factory()->forOrganization($data->organization)->forUser($user)->create();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'billable_rate' => $projectMemberFake->billable_rate,
            'member_id' => $member->getKey(),
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(ProjectMember::class, [
            'billable_rate' => $projectMemberFake->billable_rate,
            'member_id' => $member->getKey(),
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
        $data = $this->createUserWithPermission();
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
        $billableRate = 1001;
        $projectMember = ProjectMember::factory()->forProject($project)->create();
        $this->assertBillableRateServiceIsUnused();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.project-members.update', [$data->organization->getKey(), $projectMember->getKey()]), [
            'billable_rate' => $billableRate,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(ProjectMember::class, [
            'id' => $projectMember->getKey(),
            'billable_rate' => $billableRate,
            'member_id' => $projectMember->member_id,
        ]);
    }

    public function test_update_endpoints_can_update_billable_rate_and_update_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $billableRate = 1001;
        $projectMember = ProjectMember::factory()->forProject($project)->create();
        $this->mock(BillableRateService::class, function (MockInterface $mock) use ($projectMember, $billableRate): void {
            $mock->shouldReceive('updateTimeEntriesBillableRateForProjectMember')
                ->once()
                ->withArgs(fn (ProjectMember $projectMemberArg) => $projectMemberArg->is($projectMember) && $projectMemberArg->billable_rate === $billableRate);
        });
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.project-members.update', [$data->organization->getKey(), $projectMember->getKey()]), [
            'billable_rate' => $billableRate,
            'billable_rate_update_time_entries' => true,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(ProjectMember::class, [
            'id' => $projectMember->getKey(),
            'billable_rate' => $billableRate,
            'member_id' => $projectMember->member_id,
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

    public function test_destroy_endpoint_fails_if_user_has_no_permission_to_delete_project_members(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
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

    public function test_destroy_endpoint_deletes_project_member(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:delete',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($data->member)->create();
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
