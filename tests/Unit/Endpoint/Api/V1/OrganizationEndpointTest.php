<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\Role;
use App\Events\AfterCreateOrganization;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Models\Member;
use App\Models\Organization;
use App\Service\BillableRateService;
use App\Service\IpLookup\IpLookupResponseDto;
use App\Service\IpLookup\IpLookupServiceContract;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
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

    public function test_store_endpoint_creates_new_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $organizationFake = Organization::factory()->make();
        Event::fake([
            AfterCreateOrganization::class,
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.organizations.store'), [
            'name' => $organizationFake->name,
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', $organizationFake->name)
            ->where('data.is_personal', false)
            ->where('data.currency', config('app.localization.default_currency'))
            ->etc()
        );

        /** @var Organization $newOrganization */
        $newOrganization = Organization::query()->where('name', $organizationFake->name)->firstOrFail();
        $this->assertTrue($newOrganization->owner->is($data->user));
        $this->assertSame($newOrganization->getKey(), $data->user->fresh()->current_team_id);
        $this->assertDatabaseHas(Member::class, [
            'organization_id' => $newOrganization->getKey(),
            'user_id' => $data->user->getKey(),
            'role' => Role::Owner->value,
        ]);
        Event::assertDispatched(AfterCreateOrganization::class, function (AfterCreateOrganization $event) use ($newOrganization): bool {
            return $event->organization->is($newOrganization);
        });
    }

    public function test_store_endpoint_uses_ip_lookup_currency_for_new_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        $this->mock(IpLookupServiceContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('lookup')
                ->once()
                ->andReturn(new IpLookupResponseDto(null, null, 'USD'));
        });
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.organizations.store'), [
            'name' => 'Test Organization',
        ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.currency', 'USD');
        $this->assertDatabaseHas(Organization::class, [
            'name' => 'Test Organization',
            'currency' => 'USD',
            'user_id' => $data->user->getKey(),
            'personal_team' => false,
        ]);
    }

    public function test_store_endpoint_fails_if_name_is_missing(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.organizations.store'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
        $this->assertDatabaseCount(Organization::class, 1);
    }

    public function test_store_endpoint_fails_if_name_is_not_a_string(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.organizations.store'), [
            'name' => ['Test Organization'],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
        $this->assertDatabaseCount(Organization::class, 1);
    }

    public function test_store_endpoint_fails_if_name_is_too_long(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->postJson(route('api.v1.organizations.store'), [
            'name' => str_repeat('a', 256),
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
        $this->assertDatabaseCount(Organization::class, 1);
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

    public function test_update_endpoint_can_update_the_organization_name(): void
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
            'billable_rate' => null,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas(Organization::class, [
            'name' => $organizationFake->name,
        ]);
    }

    public function test_update_endpoint_can_update_formats(): void
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
            'number_format' => $organizationFake->number_format->value,
            'currency_format' => $organizationFake->currency_format->value,
            'date_format' => $organizationFake->date_format->value,
            'interval_format' => $organizationFake->interval_format->value,
            'time_format' => $organizationFake->time_format->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $data->organization->getKey(),
                'number_format' => $organizationFake->number_format->value,
                'currency_format' => $organizationFake->currency_format->value,
                'date_format' => $organizationFake->date_format->value,
                'interval_format' => $organizationFake->interval_format->value,
                'time_format' => $organizationFake->time_format->value,
            ],
        ]);
        $this->assertDatabaseHas(Organization::class, [
            'name' => $organizationFake->name,
            'number_format' => $organizationFake->number_format,
            'currency_format' => $organizationFake->currency_format,
            'date_format' => $organizationFake->date_format,
            'interval_format' => $organizationFake->interval_format,
            'time_format' => $organizationFake->time_format,
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
            'billable_rate' => $organizationFake->billable_rate,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $data->organization->getKey(),
                'name' => $data->organization->name,
                'billable_rate' => $organizationFake->billable_rate,
            ],
        ]);
        $this->assertDatabaseHas(Organization::class, [
            'id' => $data->organization->getKey(),
            'name' => $data->organization->name,
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

    public function test_update_endpoint_can_update_the_currency_of_the_organization(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:update',
        ]);
        $this->assertBillableRateServiceIsUnused();
        $data->organization->currency = 'EUR';
        $data->organization->save();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => $data->organization->name,
            'currency' => 'USD',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.currency', 'USD');
        $this->assertDatabaseHas(Organization::class, [
            'id' => $data->organization->getKey(),
            'currency' => 'USD',
        ]);
    }

    public function test_update_endpoint_fails_if_currency_is_invalid(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:update',
        ]);
        $this->assertBillableRateServiceIsUnused();
        $data->organization->currency = 'EUR';
        $data->organization->save();
        Passport::actingAs($data->user);

        // Act
        $response = $this->putJson(route('api.v1.organizations.update', [$data->organization->getKey()]), [
            'name' => $data->organization->name,
            'currency' => 'NOT_A_CURRENCY',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['currency']);
        $this->assertDatabaseHas(Organization::class, [
            'id' => $data->organization->getKey(),
            'currency' => 'EUR',
        ]);
    }

    public function test_delete_endpoint_if_user_does_not_have_permission(): void
    {
        // Arrange
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.organizations.destroy', [$data->organization->getKey()]), [
            'password' => 'password',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_delete_endpoint_fails_with_not_found_if_id_is_not_uuid(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:delete',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.organizations.destroy', ['not-uuid']), [
            'password' => 'password',
        ]);

        // Assert
        $response->assertNotFound();
    }

    public function test_delete_endpoint_fails_without_password(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:delete',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.organizations.destroy', [$data->organization->getKey()]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
        $this->assertDatabaseHas(Organization::class, [
            'id' => $data->organization->getKey(),
        ]);
    }

    public function test_delete_endpoint_fails_with_wrong_password(): void
    {
        // Arrange
        $data = $this->createUserWithPermission([
            'organizations:delete',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.organizations.destroy', [$data->organization->getKey()]), [
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
        $this->assertDatabaseHas(Organization::class, [
            'id' => $data->organization->getKey(),
        ]);
    }

    public function test_delete_endpoint_can_delete_organization(): void
    {
        // Arrange
        $this->mockPrivateStorage();
        $data = $this->createUserWithPermission([
            'organizations:delete',
        ]);
        Passport::actingAs($data->user);

        // Act
        $response = $this->deleteJson(route('api.v1.organizations.destroy', [$data->organization->getKey()]), [
            'password' => 'password',
        ]);

        // Assert
        $response->assertNoContent();
        $this->assertDatabaseMissing(Organization::class, [
            'id' => $data->organization->getKey(),
        ]);
    }
}
