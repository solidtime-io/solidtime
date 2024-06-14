<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\OrganizationController;
use App\Models\Organization;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(OrganizationController::class)]
class OrganizationEndpointTest extends ApiEndpointTestAbstract
{
    public function test_show_endpoint_fails_if_user_has_no_permission_to_view_organizations(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
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

    public function test_update_endpoint_fails_if_user_has_no_permission_to_update_organizations(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
        ]);
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
}
