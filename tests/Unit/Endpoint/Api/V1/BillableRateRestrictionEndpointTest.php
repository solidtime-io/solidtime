<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\Role;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Member;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Note: The test data is created before the organization loses access to billable rates,
 * otherwise the billable flags and rates of the test data would already be reverted on creation.
 */
#[UsesClass(Controller::class)]
class BillableRateRestrictionEndpointTest extends ApiEndpointTestAbstract
{
    public function test_project_store_ignores_billable_flag_and_rate_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:create',
        ]);
        $this->actAsOrganizationWithoutBillableRates();
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
        $response->assertCreated();
        $response->assertJsonPath('data.is_billable', false);
        $response->assertJsonPath('data.billable_rate', null);
        $project = Project::query()->findOrFail($response->json('data.id'));
        $this->assertFalse($project->is_billable);
        $this->assertNull($project->billable_rate);
    }

    public function test_project_store_sets_billable_flag_and_rate_if_organization_can_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:create',
        ]);
        $this->actAsOrganizationWithSubscription();
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
        $response->assertCreated();
        $response->assertJsonPath('data.is_billable', true);
        $response->assertJsonPath('data.billable_rate', 10000);
    }

    public function test_project_update_keeps_or_removes_existing_billable_flag_but_does_not_set_new_one_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:update',
        ]);
        $billableProject = Project::factory()->forOrganization($data->organization)->create(['is_billable' => true]);
        $removedBillableProject = Project::factory()->forOrganization($data->organization)->create(['is_billable' => true]);
        $nonBillableProject = Project::factory()->forOrganization($data->organization)->create(['is_billable' => false]);
        $this->actAsOrganizationWithoutBillableRates();
        Passport::actingAs($data->user);

        // Act
        $responseKeep = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $billableProject->getKey()]), [
            'name' => 'Still billable',
            'color' => $billableProject->color,
            'client_id' => null,
            'is_billable' => true,
        ]);
        $responseRemove = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $removedBillableProject->getKey()]), [
            'name' => 'No longer billable',
            'color' => $removedBillableProject->color,
            'client_id' => null,
            'is_billable' => false,
        ]);
        $responseSet = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $nonBillableProject->getKey()]), [
            'name' => 'Not billable',
            'color' => $nonBillableProject->color,
            'client_id' => null,
            'is_billable' => true,
        ]);

        // Assert
        $responseKeep->assertOk();
        $responseKeep->assertJsonPath('data.is_billable', true);
        $responseRemove->assertOk();
        $responseRemove->assertJsonPath('data.is_billable', false);
        $responseSet->assertOk();
        $responseSet->assertJsonPath('data.is_billable', false);
        $this->assertTrue($billableProject->refresh()->is_billable);
        $this->assertFalse($removedBillableProject->refresh()->is_billable);
        $nonBillableProject->refresh();
        $this->assertFalse($nonBillableProject->is_billable);
        $this->assertSame('Not billable', $nonBillableProject->name);
    }

    public function test_project_update_can_enable_billable_flag_if_organization_can_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'projects:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create(['is_billable' => false]);
        $this->actAsOrganizationWithSubscription();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => $project->name,
            'color' => $project->color,
            'client_id' => null,
            'is_billable' => true,
        ]);

        // Assert
        $response->assertOk();
        $this->assertTrue($project->refresh()->is_billable);
    }

    public function test_project_update_keeps_existing_billable_rate_and_time_entry_rates_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
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
        $this->actAsOrganizationWithoutBillableRates();
        Passport::actingAs($data->user);

        // Act
        $responseChange = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => 'Changed rate',
            'color' => $project->color,
            'client_id' => null,
            'is_billable' => true,
            'billable_rate' => 20000,
        ]);
        // Note: Organizations that can not use billable rates do not receive the rate of the project, so clients send `null` back
        $responseNull = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => 'Hidden rate',
            'color' => $project->color,
            'client_id' => null,
            'is_billable' => true,
            'billable_rate' => null,
        ]);
        $responseOmitted = $this->putJson(route('api.v1.projects.update', [$data->organization->getKey(), $project->getKey()]), [
            'name' => 'Omitted rate',
            'color' => $project->color,
            'client_id' => null,
            'is_billable' => true,
        ]);

        // Assert
        $responseChange->assertOk();
        $responseNull->assertOk();
        $responseOmitted->assertOk();
        $project->refresh();
        $this->assertSame('Omitted rate', $project->name);
        $this->assertSame(10000, $project->billable_rate);
        $this->assertSame(10000, $timeEntry->refresh()->billable_rate);
    }

    public function test_organization_update_ignores_billable_rate_and_employee_visibility_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:update',
        ]);
        $data->organization->billable_rate = 10000;
        $data->organization->employees_can_see_billable_rates = false;
        $data->organization->save();
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->create([
            'billable' => true,
            'billable_rate' => 10000,
        ]);
        $this->actAsOrganizationWithoutBillableRates();
        Passport::actingAs($data->user);

        // Act
        $responseRate = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => 'Organization',
            'billable_rate' => 20000,
        ]);
        $responseRemoveRate = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => 'Organization',
            'billable_rate' => null,
        ]);
        $responseVisibility = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => 'Renamed organization',
            'employees_can_see_billable_rates' => true,
        ]);

        // Assert
        $responseRate->assertOk();
        $responseRemoveRate->assertOk();
        $responseVisibility->assertOk();
        $responseVisibility->assertJsonPath('data.employees_can_see_billable_rates', false);
        $data->organization->refresh();
        $this->assertSame('Renamed organization', $data->organization->name);
        $this->assertSame(10000, $data->organization->billable_rate);
        $this->assertFalse($data->organization->employees_can_see_billable_rates);
        $this->assertSame(10000, $timeEntry->refresh()->billable_rate);
    }

    public function test_organization_update_can_remove_employee_visibility_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:update',
        ]);
        $data->organization->employees_can_see_billable_rates = true;
        $data->organization->save();
        $this->actAsOrganizationWithoutBillableRates();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => 'Organization',
            'employees_can_see_billable_rates' => false,
        ]);

        // Assert
        $response->assertOk();
        $this->assertFalse($data->organization->refresh()->employees_can_see_billable_rates);
    }

    public function test_member_update_ignores_billable_rate_but_updates_role_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'members:update',
        ]);
        $member = Member::factory()->forOrganization($data->organization)->forUser(User::factory()->create())->role(Role::Employee)->create([
            'billable_rate' => 10000,
        ]);
        $timeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($member)->create([
            'billable' => true,
            'billable_rate' => 10000,
        ]);
        $this->actAsOrganizationWithoutBillableRates();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.members.update', [$data->organization->getKey(), $member->getKey()]), [
            'billable_rate' => 20000,
            'role' => Role::Manager->value,
        ]);

        // Assert
        $response->assertOk();
        $member->refresh();
        $this->assertSame(10000, $member->billable_rate);
        $this->assertSame(Role::Manager->value, $member->role);
        $this->assertSame(10000, $timeEntry->refresh()->billable_rate);
    }

    public function test_project_member_store_and_update_ignore_billable_rate_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'project-members:create',
            'project-members:update',
        ]);
        $project = Project::factory()->forOrganization($data->organization)->create();
        $otherMember = Member::factory()->forOrganization($data->organization)->forUser(User::factory()->create())->create();
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($data->member)->create([
            'billable_rate' => 10000,
        ]);
        $this->actAsOrganizationWithoutBillableRates();
        Passport::actingAs($data->user);

        // Act
        $responseStore = $this->postJson(route('api.v1.project-members.store', [$data->organization->getKey(), $project->getKey()]), [
            'member_id' => $otherMember->getKey(),
            'billable_rate' => 10000,
        ]);
        $responseUpdate = $this->putJson(route('api.v1.project-members.update', [$data->organization->getKey(), $projectMember->getKey()]), [
            'billable_rate' => 20000,
        ]);

        // Assert
        $responseStore->assertCreated();
        $responseStore->assertJsonPath('data.billable_rate', null);
        $responseUpdate->assertOk();
        $responseUpdate->assertJsonPath('data.billable_rate', 10000);
        $this->assertSame(10000, $projectMember->refresh()->billable_rate);
        $this->assertDatabaseHas(ProjectMember::class, [
            'member_id' => $otherMember->getKey(),
            'billable_rate' => null,
        ]);
    }

    public function test_organization_exposes_billable_rate_eligibility_for_api_clients(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:view',
        ]);
        $this->actAsOrganizationWithoutBillableRates();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.organizations.show', [$data->organization->getKey()]));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.can_use_billable_rates', false);
    }

    public function test_aggregate_endpoint_hides_cost_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:view:all',
        ]);
        $start = Carbon::now()->timezone($data->user->timezone)->subDays(2);
        TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->startWithDuration($start, 3600)->create([
            'billable' => true,
            'billable_rate' => 10000,
        ]);
        $this->actAsOrganizationWithoutBillableRates();
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
        $data = $this->createUserWithPermission([
            'charts:view:own',
        ]);
        $this->actAsOrganizationWithoutBillableRates();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.charts.total-weekly-billable-amount', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_time_entry_store_creates_non_billable_time_entry_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $data->organization->billable_rate = 10000;
        $data->organization->save();
        $this->actAsOrganizationWithoutBillableRates();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'billable' => true,
            'start' => Carbon::now()->subHour()->toIso8601ZuluString(),
            'end' => Carbon::now()->toIso8601ZuluString(),
            'member_id' => $data->member->getKey(),
        ]);

        // Assert
        $response->assertCreated();
        $response->assertJsonPath('data.billable', false);
        $timeEntry = TimeEntry::query()->findOrFail($response->json('data.id'));
        $this->assertFalse($timeEntry->billable);
        $this->assertNull($timeEntry->billable_rate);
    }

    public function test_time_entry_store_creates_billable_time_entry_if_organization_can_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:create:own',
        ]);
        $this->actAsOrganizationWithSubscription();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.time-entries.store', [$data->organization->getKey()]), [
            'billable' => true,
            'start' => Carbon::now()->subHour()->toIso8601ZuluString(),
            'end' => Carbon::now()->toIso8601ZuluString(),
            'member_id' => $data->member->getKey(),
        ]);

        // Assert
        $response->assertCreated();
        $response->assertJsonPath('data.billable', true);
    }

    public function test_time_entry_update_keeps_or_removes_existing_billable_flag_but_does_not_set_new_one_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $billableTimeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->billable()
            ->startWithDuration(Carbon::now()->subHours(7), 3600)->create();
        $removedBillableTimeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->billable()
            ->startWithDuration(Carbon::now()->subHours(5), 3600)->create();
        $nonBillableTimeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->notBillable()
            ->startWithDuration(Carbon::now()->subHours(3), 3600)->create();
        $this->actAsOrganizationWithoutBillableRates();
        Passport::actingAs($data->user);

        // Act
        $responseKeep = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $billableTimeEntry->getKey()]), [
            'billable' => true,
            'description' => 'Still billable',
        ]);
        $responseRemove = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $removedBillableTimeEntry->getKey()]), [
            'billable' => false,
        ]);
        $responseSet = $this->putJson(route('api.v1.time-entries.update', [$data->organization->getKey(), $nonBillableTimeEntry->getKey()]), [
            'billable' => true,
            'description' => 'Not billable',
        ]);

        // Assert
        $responseKeep->assertOk();
        $responseKeep->assertJsonPath('data.billable', true);
        $responseRemove->assertOk();
        $responseRemove->assertJsonPath('data.billable', false);
        $responseSet->assertOk();
        $responseSet->assertJsonPath('data.billable', false);
        $this->assertTrue($billableTimeEntry->refresh()->billable);
        $this->assertSame('Still billable', $billableTimeEntry->description);
        $this->assertFalse($removedBillableTimeEntry->refresh()->billable);
        $nonBillableTimeEntry->refresh();
        $this->assertFalse($nonBillableTimeEntry->billable);
        $this->assertSame('Not billable', $nonBillableTimeEntry->description);
    }

    public function test_time_entry_update_multiple_keeps_existing_billable_flags_but_does_not_set_new_ones_if_organization_can_not_use_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'time-entries:update:own',
        ]);
        $data->organization->billable_rate = 10000;
        $data->organization->save();
        $billableTimeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->billable()->create();
        $nonBillableTimeEntry = TimeEntry::factory()->forOrganization($data->organization)->forMember($data->member)->notBillable()->create();
        $this->actAsOrganizationWithoutBillableRates();
        Passport::actingAs($data->user);

        // Act
        $response = $this->patchJson(route('api.v1.time-entries.update-multiple', [$data->organization->getKey()]), [
            'ids' => [$billableTimeEntry->getKey(), $nonBillableTimeEntry->getKey()],
            'changes' => [
                'billable' => true,
                'description' => 'Mass update',
            ],
        ]);

        // Assert
        $response->assertOk();
        $response->assertExactJson([
            'success' => [$billableTimeEntry->getKey(), $nonBillableTimeEntry->getKey()],
            'error' => [],
        ]);
        $billableTimeEntry->refresh();
        $this->assertTrue($billableTimeEntry->billable);
        $this->assertSame('Mass update', $billableTimeEntry->description);
        $nonBillableTimeEntry->refresh();
        $this->assertFalse($nonBillableTimeEntry->billable);
        $this->assertNull($nonBillableTimeEntry->billable_rate);
        $this->assertSame('Mass update', $nonBillableTimeEntry->description);
    }
}
