<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\Role;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Models\Organization;
use App\Service\BillableRateService;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(OrganizationController::class)]
class OrganizationEndpointTest extends ApiEndpointTestAbstract
{
    public function test_show_endpoint_fails_with_not_found_if_id_is_not_uuid(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:view',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.organizations.show', ['not-uuid']));

        // Assert
        $response->assertNotFound();
    }

    public function test_show_endpoint_fails_if_user_has_no_permission_to_view_organizations(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.organizations.show', [$data->organization->getKey()]));

        // Assert
        $response->assertForbidden();
    }

    public function test_show_endpoint_returns_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:view',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.organizations.show', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $data->organization->getKey());
    }

    public function test_show_endpoint_shows_billable_rate_for_members_with_role_employee_if_organization_allows_it(): void
    {
        // Arrange
        $data = $this->createUserWithRole(Role::Employee);
        $data->organization->employees_can_see_billable_rates = true;
        $data->organization->billable_rate = 100;
        $data->organization->save();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.organizations.show', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.billable_rate', 100);
    }

    public function test_show_endpoint_does_not_show_billable_rate_for_members_with_role_employee_if_organization_does_not_allow_it(): void
    {
        // Arrange
        $data = $this->createUserWithRole(Role::Employee);
        $data->organization->employees_can_see_billable_rates = false;
        $data->organization->billable_rate = 100;
        $data->organization->save();
        Passport::actingAs($data->user);

        // Act
        $response = $this->getJson(route('api.v1.organizations.show', [$data->organization->getKey()]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.billable_rate', null);
    }

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_organizations(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $this->assertBillableRateServiceIsUnused();
        $organizationFake = Organization::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => $organizationFake->name,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_update_endpoint_updates_project(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:update',
        ]);
        $this->assertBillableRateServiceIsUnused();
        $organizationFake = Organization::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => $organizationFake->name,
            'billable_rate' => $organizationFake->billable_rate,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Organization::class, [
            'name' => $organizationFake->name,
            'billable_rate' => $organizationFake->billable_rate,
        ]);
    }

    public function test_update_endpoint_can_update_billable_rate_of_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:update',
        ]);
        $this->assertBillableRateServiceIsUnused();
        $organizationFake = Organization::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => $organizationFake->name,
            'billable_rate' => $organizationFake->billable_rate,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Organization::class, [
            'name' => $organizationFake->name,
            'billable_rate' => $organizationFake->billable_rate,
        ]);
    }

    public function test_update_endpoint_can_update_the_setting_employees_can_see_billable_rates(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:update',
        ]);
        $this->assertBillableRateServiceIsUnused();
        $data->organization->employees_can_see_billable_rates = false;
        $data->organization->save();
        $organizationFake = Organization::factory()->make();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => $organizationFake->name,
            'employees_can_see_billable_rates' => true,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Organization::class, [
            'name' => $organizationFake->name,
            'employees_can_see_billable_rates' => true,
        ]);
    }

    public function test_update_endpoint_can_update_billable_rate_of_organization_and_update_time_entries(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:update',
        ]);
        $billableRate = 111;
        $organizationFake = Organization::factory()->billableRate($billableRate)->make();
        $this->mock(BillableRateService::class, function (MockInterface $mock) use ($data, $billableRate): void {
            $mock->shouldReceive('updateTimeEntriesBillableRateForOrganization')
                ->once()
                ->withArgs(fn (Organization $organization) => $organization->is($data->organization) && $organization->billable_rate === $billableRate);
        });
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => $organizationFake->name,
            'billable_rate' => $organizationFake->billable_rate,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Organization::class, [
            'name' => $organizationFake->name,
            'billable_rate' => $organizationFake->billable_rate,
        ]);
    }
}
