<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Member;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(Controller::class)]
class BillableRateRestrictionEndpointTest extends ApiEndpointTestAbstract
{
    public function test_project_store_fails_with_billable_rate_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $this->actAsOrganizationWithoutBillableRates();
        $data = $this->createUserWithPermission([
            'projects:create',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), [
            'name' => 'Project',
            'color' => '#ef5350',
            'client_id' => null,
            'is_billable' => true,
            'billable_rate' => 10000,
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('error', true);
        $response->assertJsonPath('key', 'feature_is_not_available_in_free_plan');
        $this->assertDatabaseMissing(Project::class, [
            'name' => 'Project',
        ]);
    }

    public function test_project_store_without_billable_rate_works_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $this->actAsOrganizationWithoutBillableRates();
        $data = $this->createUserWithPermission([
            'projects:create',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.projects.store', [$data->organization->getKey()]), [
            'name' => 'Project',
            'color' => '#ef5350',
            'client_id' => null,
            'is_billable' => true,
            'billable_rate' => null,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas(Project::class, [
            'name' => 'Project',
            'is_billable' => true,
            'billable_rate' => null,
        ]);
    }

    public function test_project_update_keeps_existing_billable_rate_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $this->actAsOrganizationWithoutBillableRates();
        $data = $this->createUserWithPermission([
            'projects:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create([
            'billable_rate' => 10000,
        ]);
        Passport::actingAs($data->user);

        // Act
        $responseKeep = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => 'RenamedKeep',
            'color' => $project->color,
            'client_id' => null,
            'is_billable' => true,
            'billable_rate' => 10000,
        ]);
        $responseChange = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => 'RenamedChange',
            'color' => $project->color,
            'client_id' => null,
            'is_billable' => true,
            'billable_rate' => 20000,
        ]);
        $responseRemove = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => 'RenamedRemove',
            'color' => $project->color,
            'client_id' => null,
            'is_billable' => true,
            'billable_rate' => null,
        ]);

        // Assert
        $responseKeep->assertStatus(200);
        $responseKeep->assertJsonPath('data.name', 'RenamedKeep');
        $responseChange->assertStatus(400);
        $responseChange->assertJsonPath('key', 'feature_is_not_available_in_free_plan');
        $responseRemove->assertStatus(200);
        $project->refresh();
        $this->assertSame('RenamedRemove', $project->name);
        $this->assertSame(10000, $project->billable_rate);
    }

    public function test_project_update_without_billable_rate_does_not_change_project_rate_or_time_entries_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $this->actAsOrganizationWithoutBillableRates();
        $data = $this->createUserWithPermission([
            'projects:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create([
            'billable_rate' => 10000,
            'is_billable' => true,
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->forProject($project)->create([
            'billable' => true,
            'billable_rate' => 10000,
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => 'Renamed',
            'color' => $project->color,
            'client_id' => null,
            'is_billable' => true,
            'is_archived' => true,
        ]);

        // Assert
        $response->assertStatus(200);
        $project->refresh();
        $this->assertSame('Renamed', $project->name);
        $this->assertTrue($project->is_archived);
        $this->assertSame(10000, $project->billable_rate);
        $this->assertSame(10000, $timeEntry->refresh()->billable_rate);
    }

    public function test_organization_update_fails_to_set_billable_rate_or_employee_visibility_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $this->actAsOrganizationWithoutBillableRates();
        $data = $this->createUserWithPermission([
            'organizations:update',
        ]);
        $data->organization->employees_can_see_billable_rates = false;
        $data->organization->save();
        Passport::actingAs($data->user);

        // Act
        $responseRate = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => 'Organization',
            'billable_rate' => 10000,
        ]);
        $responseVisibility = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => 'Organization',
            'employees_can_see_billable_rates' => true,
        ]);
        $responseUnchanged = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => 'Organization',
            'billable_rate' => null,
            'employees_can_see_billable_rates' => false,
        ]);

        // Assert
        $responseRate->assertStatus(400);
        $responseVisibility->assertStatus(400);
        $responseUnchanged->assertStatus(200);
        $data->organization->refresh();
        $this->assertNull($data->organization->billable_rate);
        $this->assertFalse($data->organization->employees_can_see_billable_rates);
    }

    public function test_member_update_fails_to_set_billable_rate_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $this->actAsOrganizationWithoutBillableRates();
        $data = $this->createUserWithPermission([
            'members:update',
        ]);
        $member = Member::factory()->forOrganization($data->organization)->forUser(User::factory()->create())->create([
            'billable_rate' => null,
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $member->getKey()]), [
            'billable_rate' => 10000,
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('key', 'feature_is_not_available_in_free_plan');
        $this->assertNull($member->refresh()->billable_rate);
    }

    public function test_project_member_store_and_update_fail_to_set_billable_rate_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $this->actAsOrganizationWithoutBillableRates();
        $data = $this->createUserWithPermission([
            'project-members:create',
            'project-members:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser(User::factory()->create())->create();
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($data->member)->create([
            'billable_rate' => null,
        ]);
        Passport::actingAs($data->user);

        // Act
        $responseStore = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'member_id' => $otherMember->getKey(),
            'billable_rate' => 10000,
        ]);
        $responseUpdate = $this->putJson(route('api.v1.project-members.update', [$data->organization->getKey(), $projectMember->getKey()]), [
            'billable_rate' => 10000,
        ]);

        // Assert
        $responseStore->assertStatus(400);
        $responseUpdate->assertStatus(400);
        $this->assertNull($projectMember->refresh()->billable_rate);
        $this->assertDatabaseMissing(ProjectMember::class, [
            'member_id' => $otherMember->getKey(),
        ]);
    }

    public function test_aggregate_endpoint_hides_cost_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $this->actAsOrganizationWithoutBillableRates();
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $start = Carbon::now()->timezone($data->user->timezone)->subDays(2);
        TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($start, 3600)->create([
            'billable' => true,
            'billable_rate' => 10000,
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.time-entries.aggregate', [
            $data->organization->getKey(),
        ]));

        // Assert
        $response->assertSuccessful();
        $response->assertJsonPath('data.seconds', 3600);
        $response->assertJsonPath('data.cost', null);
    }

    public function test_total_weekly_billable_amount_is_forbidden_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $this->actAsOrganizationWithoutBillableRates();
        $data = $this->createUserWithPermission([
            'charts:view:own',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.total-weekly-billable-amount', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }
}
